<?php

namespace App\Console\Commands;

use App\Models\Calendar;
use App\Services\GoogleApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class NotifyAttendance extends Command
{
    protected $signature = 'app:notify-attendance';

    protected $description = '登録者全員の稼働状況を一覧にしてSlackへ通知します';

    public function handle(GoogleApiService $service): int
    {
        $webhookUrl = config('services.attendance.summary_slack_webhook_url');
        if (empty($webhookUrl)) {
            $this->error('ATTENDANCE_SUMMARY_SLACK_WEBHOOK_URL が未設定です。');

            return self::FAILURE;
        }

        $calendars = Calendar::where('is_active', true)->orderBy('team_name')->get();

        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $today = now()->timezone('Asia/Tokyo');
        $dateLine = $today->format('Y年n月j日').'('.$weekdays[$today->dayOfWeek].')';

        $lines = [];
        foreach ($calendars as $calendar) {
            try {
                $attendance = $service->getAttendance($calendar->calendar_id);
                if ($attendance['status'] === null) {
                    continue;
                }
                $statusText = $attendance['status'];
                if ($attendance['status'] === '出社' && ! empty($attendance['work_time'])) {
                    $statusText = "出社（{$attendance['work_time']}の勤務）";
                }
                $lines[] = "• {$calendar->team_name}：{$statusText}";
            } catch (Throwable $e) {
                $lines[] = "• {$calendar->team_name}：取得失敗";
                $this->error("{$calendar->team_name}: {$e->getMessage()}");
            }
        }

        $body = "【稼働状況一覧】{$dateLine}\n\n";
        $body .= $lines === []
            ? '本日のインターン生の稼働はありません。'
            : implode("\n", $lines);

        Http::post($webhookUrl, [
            'text' => $body,
        ]);

        $this->info('一覧を送信しました。');

        return self::SUCCESS;
    }
}
