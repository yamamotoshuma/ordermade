<?php

namespace App\Services;

use App\Mail\ContactFormMail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ContactNotificationService
{
    private const DISCORD_CONTENT_LIMIT = 1900;

    /**
     * 問い合わせ内容をメール送信し、任意設定の通知先にも送る。
     */
    public function notify(string $title, string $message): void
    {
        Mail::to('ordermade@ordermade-yakyu.com')->send(new ContactFormMail($title, $message));

        $this->sendOptionalNotification('LINE', fn () => $this->pushLineNotification($title, $message));
        $this->sendOptionalNotification('Discord', fn () => $this->pushDiscordNotification($title, $message));
    }

    private function sendOptionalNotification(string $channel, callable $send): void
    {
        try {
            $send();
        } catch (Throwable $e) {
            Log::warning($channel . ' notification failed', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 既存運用に合わせて LINE Bot へ問い合わせ通知を送る。
     */
    private function pushLineNotification(string $title, string $message): void
    {
        $channelAccessToken = (string) config('services.line.channel_access_token', '');
        $recipientId = (string) config('services.line.recipient_id', '');
        $pushUrl = (string) config('services.line.push_url', 'https://api.line.me/v2/bot/message/push');

        if ($channelAccessToken === '' || $recipientId === '') {
            return;
        }

        $response = Http::withToken($channelAccessToken)
            ->acceptJson()
            ->post($pushUrl, [
                'to' => $recipientId,
                'messages' => [
                    ['type' => 'text', 'text' => '目安箱に意見がありました'],
                    ['type' => 'text', 'text' => "タイトル \n" . $title . "\n"],
                    ['type' => 'text', 'text' => "メッセージ \n" . $message],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('LINE通知に失敗しました: HTTP ' . $response->status());
        }
    }

    private function pushDiscordNotification(string $title, string $message): void
    {
        $webhookUrl = (string) config('services.discord.webhook_url', '');

        if ($webhookUrl === '') {
            return;
        }

        $content = Str::limit($this->buildDiscordMessage($title, $message), self::DISCORD_CONTENT_LIMIT, "\n...(省略)");
        $response = Http::asJson()->post($webhookUrl, [
            'content' => $content,
            'allowed_mentions' => ['parse' => []],
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Discord通知に失敗しました: HTTP ' . $response->status());
        }
    }

    private function buildDiscordMessage(string $title, string $message): string
    {
        return implode("\n", [
            '【ordermade 目安箱】意見がありました',
            'タイトル:',
            $title,
            '',
            'メッセージ:',
            $message,
        ]);
    }
}
