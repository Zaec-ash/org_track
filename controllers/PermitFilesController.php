<?php
require_once "models/PermitFilesModel.php";
require_once "models/PermitApplicationModel.php"; // used to confirm the permit belongs to this org

class PermitFilesController {

    private $permitFilesModel;
    private $permitsModel;

    public function __construct() {
        $this->permitFilesModel = new PermitFilesModel();
        $this->permitsModel = new PermitApplicationModel();
    }

    
    public function submitRequirements() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $acc_id = $_SESSION['acc_id'] ?? null;

        if (!$acc_id) {
            $_SESSION['upload_message'] = "You must be logged in to submit requirements.";
            header("Location: signin");
            exit;
        }

        $permit_id = $_POST['application_id'] ?? null;
        $campus_type = $_POST['campus_type'] ?? null;

        if (empty($permit_id) || empty($campus_type)) {
            $_SESSION['upload_message'] = "Missing application ID or campus type.";
            header("Location: signin");
            exit;
        }

        // Confirm this permit actually belongs to the logged-in org before accepting files.
        $permit = $this->permitsModel->getPermitById($permit_id, $campus_type);
        if (!$permit || (int)$permit['acc_id'] !== (int)$acc_id) {
            $_SESSION['upload_message'] = "Permit not found or does not belong to your organization.";
            header("Location: signin");
            exit;
        }
         $activity_title = $_POST['activity_title'] ?? '';

        $files = [];

        foreach ($_FILES as $fieldName => $fileData) {
            // Strip [] from field names like others[]
            $cleanFieldName = str_replace('[]', '', $fieldName);

            // Case 1: Handle Multiple Files Input (e.g. name="others[]")
            if (is_array($fileData['name'])) {
                foreach ($fileData['name'] as $index => $name) {
                    if (empty($name) || $fileData['error'][$index] === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }

                    $files[] = [
                        'doc_type'  => $cleanFieldName . '_' . ($index + 1),
                        'doc_label' => (strtolower($cleanFieldName) === 'others') ? 'Other' : ucfirst($cleanFieldName),
                        'file'      => [
                            'name'     => $fileData['name'][$index],
                            'type'     => $fileData['type'][$index],
                            'tmp_name' => $fileData['tmp_name'][$index],
                            'error'    => $fileData['error'][$index],
                            'size'     => $fileData['size'][$index],
                        ]
                    ];
                }
            }
            // Case 2: Handle Single File Input (e.g. name="proposal")
            else {
                if ($fileData['error'] === UPLOAD_ERR_NO_FILE || empty($fileData['name'])) {
                    continue;
                }

                $files[] = [
                    'doc_type'  => $cleanFieldName,
                    'doc_label' => $_POST['label_' . $cleanFieldName] ?? ucfirst($cleanFieldName),
                    'file'      => $fileData,
                ];
            }
        }

        if (empty($files)) {
            $_SESSION['upload_message'] = "No files were uploaded.";
            header("Location: dashboard_organization");
            exit;
        }

        $result = $this->permitFilesModel->saveRequirementFilesBatch($permit_id, $acc_id, $campus_type, $files);

        if (!empty($result) && !is_string($result)) {
            $updated = $this->permitsModel->updateRequirementsStatus($permit_id, $campus_type, 'submitted');

            if ($updated) {
                $_SESSION['upload_message'] = "Requirements submitted successfully.";
                OtherActions("Submitted requirements for $activity_title");
                LogOrgAction("Submitted requirements for $activity_title");
            } else {
                $_SESSION['upload_message'] = "Files uploaded, but failed to update requirement status in DB.";
            }
        } else {
            $errorMessage = is_array($result) ? implode(', ', array_keys($result)) : $result;
            $_SESSION['upload_message'] = "Some files failed to upload: " . $errorMessage;
        }

        header("Location: dashboard_organization");
        exit;
    }

    public function listFiles($permit_id, $campus_type) {
        return $this->permitFilesModel->getRequirementFilesByPermit($permit_id, $campus_type);
    }

    public function deleteFile($id, $campus_type) {
        return $this->permitFilesModel->deleteRequirementFile($id, $campus_type);
    }
    public function getFilesJson() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $permit_id = $_GET['permit_id'] ?? null;
        $campus_type = $_GET['campus_type'] ?? 'on';

        if (!$permit_id) {
            echo json_encode(['success' => false, 'message' => 'Missing permit ID']);
            exit;
        }

        $files = $this->permitFilesModel->getRequirementFilesByPermit($permit_id, $campus_type);

        echo json_encode([
            'success' => true,
            'files'   => $files
        ]);
        exit;
    }
}