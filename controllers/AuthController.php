<?php
require_once "BaseController.php";

/**
 * Anything related to getting a user in or out of the system:
 * home/signin pages, registration forms + submission, login, logout.
 * Split out of the old UsersController — this file no longer has to
 * know anything about dashboards.
 */
class AuthController extends BaseController {

    public function __construct(){
        require_once "models/AuthModel.php";
        require_once "models/StaffModel.php";
        require_once "models/OrgModel.php";
        require_once "models/ApprovedPermitModel.php";
        require_once "controllers/LogControllers.php";

        $this->logControllers = new LogControllers();
        $this->authModel  = new AuthModel();
        $this->staffModel = new StaffModel();
        $this->orgModel   = new OrgModel();
        $this->approvedPermitModel = new ApprovedPermitModel();
    }

    public function home() {
        include 'views/home.php';
    }

    public function register(){
        include "views/org_registration.php";
    }

    public function register_staff(){
        include "views/register_staff.php";
    }

    public function signin(){
        include "views/signin.php";
    }

    public function registration() {
        include "views/registration.php";
        exit();
    }

    public function permit_site(){
        include "views/registration.php";
    }

    public function interactive_veiwing(){
        $approved_permits = $this->approvedPermitModel->getAllApprovedPermits();
        include "views/student_viewing.php";
    }

    public function org_registration(){
        if(!empty($_POST)){
            $is_update = isset($_POST['is_update']) && $_POST['is_update'] === '1';

            if ($is_update) {
                $result = $this->orgModel->RenewalOrg($_POST, $_FILES);

                if($result === true){
                    $_SESSION['registration_success'] = "Renewal application submitted successfully! Your documents are now under review.";
                    OtherActions("submitted Renewal application successfully!");
                    header("Location: signin");
                } else {
                    $_SESSION['registration_error'] = is_string($result) ? $result : "Failed to update organization details. Please try again.";
                    header("Location: register");
                }
            } else {
                $result = $this->orgModel->insertOrg($_POST, $_FILES);

                if($result === true){
                    $_SESSION['registration_success'] = "Registration submitted successfully! We'll review your application and email your signin details once approved.";
                    OtherActions("Submitted Organization Application For Account!");
                    header("Location: signin");
                } else {
                    $_SESSION['registration_error'] = is_string($result) ? $result : "Something went wrong. Please try again.";
                    header("Location: register");
                }
            }
            exit();
        }
    }

    public function staff_registration() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
            $input = [
                'fname'            => trim($_POST['fname'] ?? ''),
                'mname'            => trim($_POST['mname'] ?? ''),
                'lname'            => trim($_POST['lname'] ?? ''),
                'email'            => trim($_POST['email'] ?? ''),
                'staff_id'         => trim($_POST['staff_id'] ?? ''),
                'contact_no'       => trim($_POST['contact_no'] ?? ''),
                'password'         => $_POST['password'] ?? '',
                'confirm_password' => $_POST['confirm_password'] ?? '',
            ];

            $results = $this->staffModel->insertStaff($input);

            if ($results === true) {
                $_SESSION['success_message'] = "Staff account registered successfully! Please wait for administrator activation.";
                OtherActions("Submitted Staff Application For Account!");
                header("Location: signin");
                exit();
            }

            switch ($results) {
                case "Password Mismatch":
                    $_SESSION['error_message'] = "Passwords do not match. Please try again.";
                    break;
                case "Password Weak":
                    $_SESSION['error_message'] = "Password must be at least 8 characters and include an uppercase letter, a lowercase letter, a number, and a special character.";
                    break;
                case "Invalid Staff ID":
                    $_SESSION['error_message'] = "Staff ID must be exactly 6 digits.";
                    break;
                case "Invalid Name":
                    $_SESSION['error_message'] = "Names may only contain letters, spaces, hyphens, and apostrophes.";
                    break;
                case "Invalid Contact Number":
                    $_SESSION['error_message'] = "Contact number must be in the format 0911-111-1111.";
                    break;
                case "Invalid Email Domain":
                    $_SESSION['error_message'] = "Staff registration requires a valid @bsu.edu.ph email address.";
                    break;
                case "account taken":
                    $_SESSION['error_message'] = "An account with this email address already exists.";
                    break;
                case "Staff ID Taken":
                    $_SESSION['error_message'] = "This Employee/Staff ID is already registered.";
                    break;
                default:
                    $_SESSION['error_message'] = "An unexpected error occurred during registration. Please try again.";
                    break;
            }

            header("Location: staff_registration");
            exit();
        }
        include "views/staff_registration.php";
    }

    public function LogInUser() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            $results = $this->authModel->login(['email' => $email, 'password' => $password]);

            if (in_array($results, ['staff', 'organization', 'admin'], true)) {
                $_SESSION["role"] = $results;
                $_SESSION["email"] = $email;
                $_SESSION["acc_id"] = $this->authModel->getAccIdByEmail($email);

                if ($results === 'staff' || $results === 'admin') {
                    $staff_info = $this->staffModel->getStaffByEmail($email);
                    $_SESSION["user_name"] = $staff_info ? trim($staff_info['first_name'] . ' ' . $staff_info['last_name']) : 'Staff';

                    if (function_exists('logStaffAction')) {
                        logStaffAction("Logged In");
                    }

                    header("Location: dashboard_staff");
                    exit();
                } else {
                    $org_info = $this->orgModel->getOrgByEmail($email);
                    $_SESSION["user_name"] = $org_info ? trim($org_info['first_name'] . ' ' . $org_info['last_name']) : 'Organization';

                    if (function_exists('logOrgAction')) {
                        logOrgAction("Logged In");
                    }

                    header("Location: dashboard_organization");
                    exit();
                }
            } elseif ($results === 'org_renewal') {
                $orgData = $this->orgModel->getOrgByEmail($email);

                $_SESSION["email"] = $email;
                $_SESSION["org_data"] = $orgData;
                $_SESSION["error"] = "Your account needs renewal. Please update your registration details.";

                header("Location: register");
                exit();
            } elseif ($results === 'Disabled') {
                $_SESSION["error"] = "Your account is disabled or pending activation by an administrator.";
                header("Location: signin");
                exit();
            } else {
                $_SESSION["error"] = "Invalid email or password.";
                header("Location: signin");
                exit();
            }
        }
    }

    public function logout() {
        if($_SESSION['role'] == "staff" || $_SESSION['role'] == "admin"){
            LogStaffAction("Logged Out");
        } else {
            LogOrgAction("Logged Out");
        }

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();

        header("Location: signin");
        exit();
    }
}