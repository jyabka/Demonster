<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once 'db.php';

$username = "";
$email    = "";
$errors   = array();

if (isset($_POST['register_submit'])) {
	register();
}

if (isset($_POST['login_submit'])) {
	login();
}

if (isset($_GET['logout'])) {
	session_destroy();
	unset($_SESSION['user']);
	header("location: ./login.php");
}

function register()
{
	global $db, $errors;

	$username    =  e($_POST['username']);
	$email       =  e($_POST['email']);
	$password_one =  e($_POST['password_one']);
	$password_two  =  e($_POST['password_two']);

	if (empty($username)) {
		array_push($errors, "Username is required");
	}
	if (empty($email)) {
		array_push($errors, "Email is required");
	}
	if (!empty($email)) {
		$sql = "SELECT * FROM users WHERE email=?";
		$stmt = $db->prepare($sql);
		$stmt->bind_param("s", $email);
		$stmt->execute();
		$result = $stmt->get_result();
		if (mysqli_num_rows($result) > 0) {
			array_push($errors, "Email already exist");
		}
	}
	if (empty($password_one)) {
		array_push($errors, "Password is required");
	}
	if ($password_one != $password_two) {
		array_push($errors, "The two passwords do not match");
	}

	if (count($errors) == 0) {
		$password = password_hash($password_one, PASSWORD_DEFAULT); //encrypt the password before saving in the database

		if (isset($_POST['user_type'])) {
			$user_type = e($_POST['user_type']);
			$query = "INSERT INTO users (username, email, user_type, password) 
						  VALUES('$username', '$email', '$user_type', '$password')";
			mysqli_query($db, $query);
			$_SESSION['success']  = "New user successfully created!!";
			header('location: home.php');
		} else {
			$query = "INSERT INTO users (username, email, user_type, password) 
						  VALUES('$username', '$email', 'User', '$password')";
			mysqli_query($db, $query);

			// get id of the created user
			$logged_in_user_id = mysqli_insert_id($db);

			$_SESSION['user'] = getUserById($logged_in_user_id); // put logged in user in session
			$_SESSION['success']  = "You are now logged in";
			header('location: index.php');
		}
	}
}

function getUserById($id)
{
	global $db;
	$query = "SELECT * FROM users WHERE id=" . $id;
	$result = mysqli_query($db, $query);

	$user = mysqli_fetch_assoc($result);
	return $user;
}

// LOGIN USER
function login()
{
	global $db, $username, $errors;

	$email = e($_POST['email']);
	$password_input = e($_POST['password']);

	if (empty($email)) {
		array_push($errors, "email is required");
	}
	if (empty($password_input)) {
		array_push($errors, "Password is required");
	}

	if (count($errors) == 0) {

		$query = "SELECT * FROM users WHERE email='$email' LIMIT 1";
		$results = mysqli_query($db, $query);
		if (mysqli_num_rows($results) == 1) {
			$logged_in_user = mysqli_fetch_assoc($results);
			$hashed = $logged_in_user["password"];
			$verify = password_verify($password_input, $hashed);
			if ($verify) {
				if ($logged_in_user['user_type'] == 'admin') {

					$_SESSION['user'] = $logged_in_user;
					$_SESSION['success']  = "You are now logged in";
					header('location: admin/home.php');
				} else {
					$_SESSION['user'] = $logged_in_user;
					$_SESSION['success']  = "You are now logged in";
					header('location: index.php');
				}
			} else {
				array_push($errors, "Invalid Credentials");
			}
		} else {
			array_push($errors, "Invalid Credentials");
		}

		// if (mysqli_num_rows($results) == 1) { // user found
		// 	// check if user is admin or use
	}
}


function isLoggedIn()
{
	if (isset($_SESSION['user'])) {
		return true;
	} else {
		return false;
	}
}


function isAdmin()
{
	if (isset($_SESSION['user']) && $_SESSION['user']['user_type'] == 'Admin') {
		return true;
	} else {
		return false;
	}
}

function e($val)
{
	global $db;
	return mysqli_real_escape_string($db, trim($val));
}

function display_error()
{
	global $errors;

	if (count($errors) > 0) {
		foreach ($errors as $error) {
			echo '<div class="alert alert-warning">';
			echo $error . '<br>';
			echo '</div>';
		}
	}
}
