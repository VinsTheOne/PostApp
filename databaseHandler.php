<?php
  $username=$email=$password="";
  $conn = connectDB("localhost", "root", "");
  if($_SERVER["REQUEST_METHOD"] == "POST"){
    if (isset($_POST["postPost"])){
      insertPost($conn, $_POST["title"], $_POST["text"], $_POST["bool"] ,getUserID($conn, $_SESSION["username"]));
    }
    if(isset($_POST["deleteButton"])){
      deletePost($conn, $_POST["deleteInput"]);
    }
    else if (isset($_POST["login"])){
      $username = filter_var($_POST["username"], FILTER_SANITIZE_STRING);
      $password = $_POST["password"];
      login($conn, $username, $password);

    }
    else if (isset($_POST["register"])){
      $username = filter_var($_POST["username"], FILTER_SANITIZE_STRING);
      $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
      $password = ($_POST["password"]);
      registerUser($conn, $username, $email, $password);
    }
  }
  closeConn($conn);

  // INITIALIZE CONNECTION TO DATABASE
  function connectDB($servername, $username, $password){
    $conn = new mysqli($servername, $username, $password);
    if ($conn->connect_error) {
      echo "<body><script>alert('Connection failed');</script></body>";
    }
    return $conn;
  }

  // CLOSE CONNECTION WITH DATABASE
  function closeConn($conn){
    $conn->close();
    return true;
  }

  // LOGIN HANDLING
  function login($conn, $username, $password){
    $sql = "SELECT * FROM postapp.users WHERE BINARY username='".$username."'";
    $result = $conn->query($sql);

    // CHECK IF ACCOUNT WITH GIVEN USERNAME EXISTS
    if ($result->num_rows == 0){
      $_SESSION["username"] = $username;
      header("Location: index.php?noUser=true");
    }
    else{
      $row = $result->fetch_assoc();
      $dbUsername = $row["username"];
      $dbPassword = $row["password"];

      // CHECK IF INSERTED PASSWORD (HASHED) MATCHES HASHED PASSWORD FROM DATABASE
      if (password_verify($password, $dbPassword)){
        header("Location: main/main.php?username=".$username); // LOGIN ALLOWED, REDIRECT TO MAIN.PHP
      }
      else{
        $_SESSION["username"] = $username;
        header("Location: index.php?wrongPass=true"); // WRONG PASSWORD
      }
    }
  }

  // USER REGISTRATION HANDLING
  function registerUser($conn, $username, $email, $password){
    $sql = "SELECT * FROM postapp.users";
    $result = $conn->query($sql);
    $row = $result->fetch_all();
    $flag = 1;

    // CHECK IF E-MAIL ADRESS IS IN VALID FORMAT
    if ($flag){
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $flag = 0;
        $_SESSION["regusername"] = $username;
        $_SESSION["email"] = $email;
        header("Location: index.php?wrongEmail=true&switch=true");
      }

      for ($i = 0; $i < getUserCount($conn); $i++){
        // CHECK IF ACCOUNT WITH GIVEN USERNAME ALREADY EXISTS
        if ($username == $row[$i][1]){
          $flag = 0;
          $_SESSION["regusername"] = $username;
          $_SESSION["email"] = $email;
          header("Location: index.php?userExists=true&switch=true");
        }
        // CHECK IF ACCOUNT WITH GIVEN E-MAIL ALREADY EXISTS
        else if ($email == $row[$i][2]){
          $flag = 0;
          $_SESSION["regusername"] = $username;
          $_SESSION["email"] = $email;
          header("Location: index.php?emailExists=true&switch=true");
        }

      }
    }
    // REGISTRATION ALLOWED
    if ($flag){
      $hadhedPass = password_hash($password, PASSWORD_DEFAULT);
      $sql = "INSERT INTO postapp.users(username, email, password) VALUES ('".$username."', '".$email."', '".$hadhedPass."')";
      $result = $conn->query($sql);
      header("Location: index.php?reg=true");
    }
  }

  function getUserCount($conn){
    $sql = "SELECT COUNT(*) FROM postapp.users";
    $result = $conn->query($sql);
    $count = $result->fetch_all();
    return $count[0][0];
  }

  function getUserID($conn, $username){
    $sql = "SELECT user_id FROM postapp.users WHERE username='".$username."'";
    $result = $conn->query($sql);
    $userID = $result->fetch_all();
    return $userID[0][0];
  }

  function getUserPostsCount($conn, $userID){
    $sql = "SELECT COUNT(*) FROM postapp.posts WHERE user_id='".$userID."'";
    $result = $conn->query($sql);
    $postCount = $result->fetch_all();
    return $postCount[0][0];
  }

  function getUserPosts($conn, $userID){
    $sql = "SELECT * FROM postapp.posts WHERE user_id='".$userID."'";
    $result = $conn->query($sql);
    $posts = $result->fetch_all();
    return $posts;
  }

  function getUsernameOfPost($conn, $postFK){
    $sql = "SELECT username FROM postapp.users WHERE users.user_id=".$postFK;
    $result = $conn->query($sql);
    $username = $result->fetch_all();
    return $username[0][0];
  }

  function getPublicPostsCount($conn){
    $sql = "SELECT COUNT(*) FROM postapp.posts WHERE public='1'";
    $result = $conn->query($sql);
    $postCount = $result->fetch_all();
    return $postCount[0][0];
  }

  function getPublicPosts($conn){
    $sql = "SELECT * FROM postapp.posts WHERE public='1'";
    $result = $conn->query($sql);
    $posts = $result->fetch_all();
    return $posts;
  }

  function insertPost($conn, $title, $text, $bool, $userID){
    if (strlen($title) > 50){
      $_SESSION["title"] = $title;
      $_SESSION["text"] = $text;
      header("Location: main.php?titlelen=false");
    }

    else if (strlen($text) > 140){
      $_SESSION["title"] = $title;
      $_SESSION["text"] = $text;
      header("Location: main.php?textlen=false");
    }

    else if (empty($bool)){
      $_SESSION["title"] = $title;
      $_SESSION["text"] = $text;
      header("Location: main.php?bool=false");
    }
    else{
      $_SESSION["title"] = "";
      $_SESSION["text"] = "";
      if ($bool == "private"){
        $sql = "INSERT INTO postapp.posts(postTitle, postText, time, public, user_id) VALUES ('".$title."', '".$text."', current_timestamp(), '0' ,'".$userID."')";
        $result = $conn->query($sql);
        return $result;
      }
      else{
        $sql = "INSERT INTO postapp.posts(postTitle, postText, time, public, user_id) VALUES ('".$title."', '".$text."', current_timestamp(), '1','".$userID."')";
        $result = $conn->query($sql);
        return $result;
      }
    }


  }
  function deletePost($conn, $postID){
    $sql = "DELETE FROM postapp.posts WHERE post_id='".$postID."'";
    $result = $conn->query($sql);
    return $result;
  }

?>
