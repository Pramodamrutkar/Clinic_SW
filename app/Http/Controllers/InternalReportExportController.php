<?php

namespace App\Http\Controllers;
use App\Exports\MultiSheetDataReport;
use App\Models\CasheAppModel;
use App\Models\ConfigEmailsModel;
use App\Models\ErrorLogModel;
use Illuminate\Database\QueryException;
use Excel;
use Exception;

class InternalReportExportController extends Controller
{
    
    public function export(){
        try{
            $fileName = "InternalReport-".date("Y-m-d").".xlsx";
            $result  = Excel::store(new MultiSheetDataReport,$fileName);
            if($result == 1){
                $subject = "Creditlinks extract for ".date("Y-m-d");
                $emailIds = ConfigEmailsModel::getEmailIds("INTERNALREPORT"); 
                $emails = explode(",",$emailIds);
                $messagePage = "internalreport"; // store in resource view
                $attachment = storage_path("app/".$fileName);
                $data = array("toEmail" => $emails, "subject" => $subject, "attachment" => $attachment);
                CasheAppModel::sendTemplateEmails($messagePage,$data,$attachment);  
                return Response([
                    'status' => 'true',
                    'message' => 'Internal Report has been sent successfully.'
                ],200); 
            }
        }catch (QueryException $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message);
            echo ErrorLogModel::genericMessage();
        } catch (Exception $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message);
            echo ErrorLogModel::genericMessage();
        }
        
    }

}
