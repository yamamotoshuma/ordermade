<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleSheetsOrderImporter
{
    public function fetchOrderRows(): array
    {
        $spreadsheetId = (string) config('services.google_sheets.spreadsheet_id');
        $sheetGid = (int) config('services.google_sheets.sheet_gid', 0);
        $startRange = (string) config('services.google_sheets.start_range', 'B6:D20');

        if ($spreadsheetId === '') {
            throw new RuntimeException('スプレッドシートIDが設定されていません。');
        }

        $sheetTitle = $this->fetchSheetTitle($spreadsheetId, $sheetGid);
        $range = sprintf("'%s'!%s", str_replace("'", "''", $sheetTitle), $startRange);
        $accessToken = $this->getAccessToken();

        $response = Http::timeout(15)
            ->withToken($accessToken)
            ->get(sprintf(
                'https://sheets.googleapis.com/v4/spreadsheets/%s/values/%s',
                $spreadsheetId,
                rawurlencode($range)
            ));

        if ($response->failed()) {
            throw new RuntimeException('スプレッドシートの値取得に失敗しました。');
        }

        $values = $response->json('values', []);
        $rows = [];

        foreach ($values as $index => $valueRow) {
            [$battingOrder, $positionName, $playerName] = array_pad($valueRow, 3, '');

            $battingOrder = trim((string) $battingOrder);
            $positionName = trim((string) $positionName);
            $playerName = trim((string) $playerName);

            if ($battingOrder === '' && $positionName === '' && $playerName === '') {
                continue;
            }

            if ($battingOrder === '' || $positionName === '' || $playerName === '') {
                continue;
            }

            if (! is_numeric($battingOrder)) {
                continue;
            }

            $rows[] = [
                'battingOrder' => (int) $battingOrder,
                'positionName' => $positionName,
                'playerName' => $playerName,
            ];
        }

        return $rows;
    }

    private function fetchSheetTitle(string $spreadsheetId, int $sheetGid): string
    {
        $accessToken = $this->getAccessToken();

        $response = Http::timeout(15)
            ->withToken($accessToken)
            ->get(sprintf(
                'https://sheets.googleapis.com/v4/spreadsheets/%s?fields=sheets(properties(sheetId,title))',
                $spreadsheetId
            ));

        if ($response->failed()) {
            throw new RuntimeException('スプレッドシート情報の取得に失敗しました。');
        }

        $sheets = $response->json('sheets', []);

        foreach ($sheets as $sheet) {
            $properties = $sheet['properties'] ?? [];

            if ((int) ($properties['sheetId'] ?? -1) === $sheetGid) {
                $title = (string) ($properties['title'] ?? '');

                if ($title !== '') {
                    return $title;
                }
            }
        }

        throw new RuntimeException('指定されたシートが見つかりません。');
    }

    private function getAccessToken(): string
    {
        $credentials = $this->loadCredentials();
        $issuedAt = time();
        $assertion = $this->buildJwtAssertion(
            [
                'alg' => 'RS256',
                'typ' => 'JWT',
            ],
            [
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/spreadsheets.readonly',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $issuedAt,
                'exp' => $issuedAt + 3600,
            ],
            $credentials['private_key']
        );

        $response = Http::asForm()
            ->timeout(15)
            ->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Google API の認証に失敗しました。');
        }

        $accessToken = (string) $response->json('access_token', '');

        if ($accessToken === '') {
            throw new RuntimeException('Google API のアクセストークンを取得できませんでした。');
        }

        return $accessToken;
    }

    private function loadCredentials(): array
    {
        $configuredPath = (string) config('services.google_sheets.service_account_path');
        $path = str_starts_with($configuredPath, DIRECTORY_SEPARATOR)
            ? $configuredPath
            : base_path($configuredPath);

        if ($configuredPath === '' || ! is_file($path)) {
            throw new RuntimeException('Google service account JSON が見つかりません。');
        }

        $credentials = json_decode((string) file_get_contents($path), true);

        if (! is_array($credentials) || empty($credentials['client_email']) || empty($credentials['private_key'])) {
            throw new RuntimeException('Google service account JSON の内容が不正です。');
        }

        return $credentials;
    }

    private function buildJwtAssertion(array $header, array $payload, string $privateKey): string
    {
        $segments = [
            $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES)),
            $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES)),
        ];

        $signingInput = implode('.', $segments);
        $signature = '';

        if (! openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Google API 用の署名生成に失敗しました。');
        }

        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
