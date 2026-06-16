<?php

namespace Tests\Feature;

use App\Mail\ContactFormMail;
use App\Services\ContactNotificationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactNotificationServiceTest extends TestCase
{
    public function test_it_sends_contact_mail_line_and_discord_notifications_when_configured(): void
    {
        Mail::fake();
        Http::fake([
            'https://api.line.me/*' => Http::response([], 200),
            'https://discord.test/webhook' => Http::response('', 204),
        ]);

        config()->set('services.line.push_url', 'https://api.line.me/v2/bot/message/push');
        config()->set('services.line.channel_access_token', 'line-token');
        config()->set('services.line.recipient_id', 'line-recipient');
        config()->set('services.discord.webhook_url', 'https://discord.test/webhook');

        app(ContactNotificationService::class)->notify('練習試合について', '参加希望です');

        Mail::assertSent(ContactFormMail::class, function (ContactFormMail $mail): bool {
            return $mail->title === '練習試合について' && $mail->message === '参加希望です';
        });
        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.line.me/v2/bot/message/push'
                && $request['to'] === 'line-recipient'
                && $request['messages'][1]['text'] === "タイトル \n練習試合について\n";
        });
        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://discord.test/webhook'
                && str_contains($request['content'], '【ordermade 目安箱】意見がありました')
                && str_contains($request['content'], '練習試合について')
                && $request['allowed_mentions'] === ['parse' => []];
        });
    }

    public function test_optional_notification_failures_do_not_fail_contact_mail_delivery(): void
    {
        Mail::fake();
        Http::fake([
            '*' => Http::response('rate limited', 429),
        ]);

        config()->set('services.line.push_url', 'https://api.line.me/v2/bot/message/push');
        config()->set('services.line.channel_access_token', 'line-token');
        config()->set('services.line.recipient_id', 'line-recipient');
        config()->set('services.discord.webhook_url', 'https://discord.test/webhook');

        app(ContactNotificationService::class)->notify('タイトル', '本文');

        Mail::assertSent(ContactFormMail::class);
        Http::assertSentCount(2);
    }
}
