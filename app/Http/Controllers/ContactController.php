<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactFormMail;

class ContactController extends Controller
{
    public function index(){
        return view('contact');
    }


    // 問い合わせが成功したことを示すページにリダイレクトするなどの処理を追加することもできます
    public function store(Request $request)
    {
        // メール送信
        $title = $request->input('title');
        $message = $request->input('message');

        Mail::to('ordermade@ordermade-yakyu.com')->send(new ContactFormMail($title,$message));

        $channel_access_token = 'dDrl5yZ3vGjlzLrXSzV66jmkZwlPwzd0xKiD+w59LtJvelX7wbf1Hpab9Mr/N+wULjZurAUsZYyH7GyHtv7a+XVfR8sbj6YLdvuJZ2I9bb24/Ti1nb3CocDQsQq5rbe2tyRHxu6YeXBGTs9QgVmNdgdB04t89/1O/w1cDnyilFU=';

        $url = 'https://api.line.me/v2/bot/message/push';

        $data = array(
            'to' => 'C2275285be67a7a4c26d9ba6d90d71a85',
            'messages' => array(
                array(
                    'type' => 'text',
                    'text' => '目安箱に意見がありました'
                ),
                array(
                    'type' => 'text',
                    'text' => "タイトル \n" . $title ."\n"
                ),
                array(
                    'type' => 'text',
                    'text' => "メッセージ \n" . $message
                )
            )
        );

        $payload = json_encode($data);

        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $channel_access_token
        );

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_VERBOSE, true);

        $result = curl_exec($ch);

        if ($result === false) {
            echo 'Curl error: ' . curl_error($ch);
        } else {
            echo 'Response: ' . $result;
        }

        curl_close($ch);

        return redirect('contact')->with('success', 'ご意見ありがとうございます。後ほどグループラインで返信します。');
    }
}
