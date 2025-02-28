<?php
require_once "model/UserModel.php";
require_once "view/helpers.php";
require_once './vendor/autoload.php';
require_once './env.php';


$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

class AuthController
{
    private $UserModel;
    private $googleClient;

    public function __construct()
    {
        $this->UserModel = new UserModel();

        $this->googleClient = new Google_Client();
        $this->googleClient->setClientId($_ENV['GOOGLE_CLIENT_ID']);
        $this->googleClient->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
        $this->googleClient->setRedirectUri($_ENV['GOOGLE_REDIRECT_URL']);
        $this->googleClient->addScope("email");
        $this->googleClient->addScope("profile");
    }

    public function redirectToGoogle()
    {
        $authUrl = $this->googleClient->createAuthUrl();
        header("Location: $authUrl");
        exit();
    }

    public function googleCallback()
    {
        if (!isset($_GET['code'])) {
            header('Location: /login');
            exit();
        }

        $token = $this->googleClient->fetchAccessTokenWithAuthCode($_GET['code']);
        $this->googleClient->setAccessToken($token);

        $googleService = new Google_Service_Oauth2($this->googleClient);
        $googleUser = $googleService->userinfo->get();

        $email = $googleUser->email;
        $name = $googleUser->name;

        $user = $this->UserModel->getUserByEmail($email);
        if (!$user) {
            $this->UserModel->registerGoogle($name, $email, null, null, 'google');
            $user = $this->UserModel->getUserByEmail($email);
        }

        $_SESSION['users'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'role' => $user['role'],
            'auth_provider' => 'google'
        ];
        $_SESSION['login_success'] = true;

        header('Location: /');
        exit();
    }


    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $password = trim($_POST['password']);
            $confirm_password = trim($_POST['confirm_password']);

            if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
                $_SESSION['register_error'] = 'Vui lòng điền đầy đủ thông tin.';
                header('Location: /register');
                exit();
            }

            if ($password !== $confirm_password) {
                $_SESSION['register_error'] = 'Mật khẩu xác nhận không khớp.';
                header('Location: /register');
                exit();
            }
            $existingUser = $this->UserModel->checkEmailExists($email);
            if ($existingUser) {
                $_SESSION['register_error'] = 'Email này đã được đăng ký.';
                header('Location: /register');
                exit();
            }

            $result = $this->UserModel->register($name, $email, $password, $phone);
            if ($result) {
                $_SESSION['register_success'] = 'Đăng ký thành công! Vui lòng đăng nhập.';
                header('Location: /login');
                exit();
            } else {
                $_SESSION['register_error'] = 'Đăng ký thất bại. Vui lòng thử lại.';
                header('Location: /register');
                exit();
            }
        } else {
            renderView('auth/register.php', [], 'Register');
        }
    }


    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'];
            $password = $_POST['password'];

            $user = $this->UserModel->login($email, $password);
            if ($user) {
                $_SESSION['users'] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'phone' => $user['phone'],
                    'role' => $user['role']
                ];
                $_SESSION['login_success'] = true;

                header('Location: /');
                exit();
            } else {
                $_SESSION['login_failed'] = true;
                header('Location: /login');
                exit();
            }
        } else {
            renderView('auth/login.php', [], 'Login');
        }
    }


    public function unauthorized()
    {
        renderView('unauthorized', [], 'Unauthorized Access');
    }

    public function logout()
    {
        session_start();
        session_unset();
        session_destroy();
        header('Location: /login');
        exit();
    }
    public function indexUser()
    {
        $users = $this->UserModel->getAllUsers();
        renderView("admin/user/index.php", compact('users'), "User List", 'admin');
    }

    public function show($id)
    {
        // Get user details
        $user = $this->UserModel->getUserById($id);
        
        if (!$user) {
            $_SESSION['error'] = "Không tìm thấy người dùng.";
            header('Location: /');
            exit;
        }

        // Get all addresses for this user

        // Render the Blade template with user and addresses
        renderView("profile/index.php", compact('user', 'addresses'), "User Details");
    }


    public function delete($id)
    {
        $user = $this->UserModel->getUserById($id);

        if (!$user) {
            $_SESSION['error'] = "Người dùng không tồn tại!";
            header("Location: /admin/users");
            exit;
        }

        if ($user['role'] === 'admin') {
            $_SESSION['error'] = "Không thể xóa tài khoản quản trị viên!";
            header("Location: /admin/users");
            exit;
        }

        if ($this->UserModel->deleteUser($id)) {
            $_SESSION['success'] = "Xóa user thành công!";
        } else {
            $_SESSION['error'] = "Có lỗi xảy ra khi xóa user!";
        }

        header("Location: /admin/users");
        exit;
    }
    public function editRole($id)
    {
        $user = $this->UserModel->getUserById($id);

        if (!$user) {
            $_SESSION['error'] = "Người dùng không tồn tại!";
            header("Location: /admin/users");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $role = $_POST['role'];
            $name = $_POST['name'];
            $phone = $_POST['phone'];
            if (!in_array($role, ['user', 'admin'])) {
                $_SESSION['error'] = "Vai trò không hợp lệ!";
                header("Location: /user/edit/$id");
                exit;
            }
            if ($this->UserModel->updateRole($id, $role, $name, $phone)) {
                $_SESSION['success'] = "Cập nhật vai trò thành công!";
            } else {
                $_SESSION['error'] = "Có lỗi xảy ra khi cập nhật!";
            }

            header("Location: /admin/users");
            exit;
        }
        renderView("admin/user/edit.php", compact('user'), "Edit Role", 'admin');
    }
}
