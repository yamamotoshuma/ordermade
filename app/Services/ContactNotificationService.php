<?php

namespace App\Services;

use App\Mail\ContactFormMail;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class ContactNotificationService
{
    /**
     * 問い合わせ内容をメール送信し、あわせて LINE 通知も飛ばす。
     */
    public function notify(string $title, string $message): void
    {
        Mail::to('ordermade@ordermade-yakyu.com')->send(new ContactFormMail($title, $message));
        $this->pushLineNotification($title, $message);
    }

    /**
     * 既存運用に合わせて LINE Bot へ問い合わせ通知を送る。
     */
    private function pushLineNotification(string $title, string $message): void
    {
        $channelAccessToken = 'dDrl5yZ3vGjlzLrXSzV66jmkZwlPwzd0xKiD+w59LtJvelX7wbf1Hpab9Mr/N+wULjZurAUsZYyH7GyHtv7a+XVfR8sbj6YLdvuJZ2I9bb24/Ti1nb3CocDQsQq5rbe2tyRHxu6YeXBGTs9QgVmNdgdB04t89/1O/w1cDnyilFU=';

        $payload = json_encode([
            'to' => 'C2275285be67a7a4c26d9ba6d90d71a85',
            'messages' => [
                ['type' => 'text', 'text' => '目安箱に意見がありました'],
                ['type' => 'text', 'text' => "タイトル \n" . $title . "\n"],
                ['type' => 'text', 'text' => "メッセージ \n" . $message],
            ],
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init('https://api.line.me/v2/bot/message/push');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $channelAccessToken,
        ]);

        $result = curl_exec($ch);

        if ($result === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('LINE通知に失敗しました: ' . $error);
        }

        curl_close($ch);
    }
}
