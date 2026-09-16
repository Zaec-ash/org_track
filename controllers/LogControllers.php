<?php
require_once "BaseController.php";
class   LogControllers extends BaseController{
	public function __construct(){
  }
  public function clearSystemLogs($filePath) {
    if (!file_exists($filePath)) {
        return true; // nothing to clear
    }
    // Empty the file rather than deleting it, so future appends still work
    return file_put_contents($filePath, '') !== false;
}

    // Add the function here
    public function getParsedSystemLogs($filePath = '../staff_audit_log.txt') {
        $logs = [];
        if (!file_exists($filePath)) {
            return $logs;
        }

        $fileLines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach (array_reverse($fileLines) as $line) {
            if (preg_match('/^\[(.*?)\]\s+(.*?)\s+-\s+(.*)$/', $line, $matches)) {
                $timestamp = $matches[1];
                $user      = $matches[2];
                $message   = $matches[3];
                $event     = "$user - $message";

                $status = 'Info';
                if (stripos($message, 'Approved') !== false || stripos($message, 'Logged In') !== false) {
                    $status = 'Success';
                } elseif (stripos($message, 'Rejected') !== false) {
                    $status = 'Warning';
                }

                $logs[] = [
                    'timestamp' => $timestamp,
                    'event'     => $event,
                    'status'    => $status
                ];
            }
        }
        return $logs;
    }
  // Add the function here
    public function getOrgParsedSystemLogs($filePath = '../org_audit_log.txt') {
        $logs = [];
        if (!file_exists($filePath)) {
            return $logs;
        }

        $fileLines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach (array_reverse($fileLines) as $line) {
            if (preg_match('/^\[(.*?)\]\s+(.*?)\s+-\s+(.*)$/', $line, $matches)) {
                $timestamp = $matches[1];
                $user      = $matches[2];
                $message   = $matches[3];
                $event     = "$user - $message";

                $status = 'Info';
                if (stripos($message, 'Approved') !== false || stripos($message, 'Logged In') !== false) {
                    $status = 'Success';
                } elseif (stripos($message, 'Rejected') !== false) {
                    $status = 'Warning';
                }

                $logs[] = [
                    'timestamp' => $timestamp,
                    'event'     => $event,
                    'status'    => $status
                ];
            }
        }
        return $logs;
    }

}