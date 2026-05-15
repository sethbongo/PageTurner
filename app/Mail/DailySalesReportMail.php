<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DailySalesReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $reportDate;
    public int $totalOrders;
    public float $totalRevenue;
    private string $attachmentPath;

    public function __construct(string $reportDate, int $totalOrders, float $totalRevenue, string $attachmentPath)
    {
        $this->reportDate = $reportDate;
        $this->totalOrders = $totalOrders;
        $this->totalRevenue = $totalRevenue;
        $this->attachmentPath = $attachmentPath;
    }

    public function build(): self
    {
        return $this->subject('Daily Sales Report - ' . $this->reportDate)
            ->view('emails.daily-sales-report')
            ->attach($this->attachmentPath, [
                'as' => 'daily-sales-report-' . $this->reportDate . '.csv',
                'mime' => 'text/csv',
            ]);
    }
}
