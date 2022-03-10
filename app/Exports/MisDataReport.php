<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use DB;

class MisDataReport implements FromCollection,WithEvents,WithTitle,ShouldAutoSize,WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $mis_data_report = DB::select('CALL mis_data_report()');

        $misDataReportArray = json_decode(json_encode($mis_data_report, true),true);

        foreach ($misDataReportArray as $key => $value) {
            $headingsData[] = array(
                "Date" => $value["CurrentDate"] ?? "",
                "How many unique users landed on the first screen" => $value["UniqueUsers"] ?? 0,
                "How many users proceeded to submit their mobile and email (Completion of screen 1)" => $value["UsersMobileEmail"] ?? 0,
                "Returning user screen one" => $value["returnUserScreenOne"] ?? 0,
                "How many unique users requested an OTP" => $value["UniqueOTPRequests"] ?? 0,
                "How many total OTP requests were made" => $value["OTPRequests"] ?? 0,
                "How many total OTP requests on Email" => $value["OTPonEmail"] ?? 0,
                "How many total OTP requests on mobile" => $value["OTPonMobile"] ?? 0,
                "How many successful OTP entries were there" => $value["OTPUsed"] ?? 0,
                "Welcome back returning users" => $value["returnUser"] ?? 0,
                "How many people completed the intake form" => $value["AppsCreated"] ?? 0,
				"Drop Out" => $value["dropOut"] ?? 0
            );
        }

        return collect([$headingsData]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getStyle('A1:Z1')->applyFromArray([
                    'font' => [
                        'bold' => true
                    ]
                ]);
            }
        ];
    }
    public function title(): string
    {
        $title = "User Journey";
        return $title;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Unique Users (Screen 1)',
            'Users Login Mobile and Email (Completion of screen 1)',
			'Returning User (Screen 1)',
            'OTP Requested By Unique User',
            'Requested OTP',
            'OTP Requested On Email',
            'OTP Requested On Mobile',
            'Used OTP',
            'Welcome Back (Returning User)',
            'Intake Form Submission',
			'Drop out'
        ];
    }

}
