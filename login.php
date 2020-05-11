<?php
   session_start();

   require_once (dirname(__FILE__)."/util/auth_check.php");
   if(isLogged()) {
      header("Location:index.php");
   }

   require_once (dirname(__FILE__)."/util/dbconnect.php");
   require_once (dirname(__FILE__)."/util/config.php");
   require_once (dirname(__FILE__)."/util/token_gen.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="utf-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1">
   <title>Login</title>
   <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" integrity="sha384-HSMxcRTRxnN+Bdg0JdbxYKrThecOKuH5zCYotlSAcp1+c8xmyTe9GYg1l9a69psu" crossorigin="anonymous">
   <link rel="stylesheet" href="./css/form_table.css">
</head>
   <body>

      <div align="center">

         <h2>Login</h2>

         <form action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" method="POST">
            <table>
               <tr>
                  <td id="label">Email</td>
                  <td id="padding"><input type="email" name="email" value="<?php if(isset($_POST['email'])) echo $_POST['email']; ?>" required></td>
               </tr>
               <tr>
                  <td id="label">Password</td>
                  <td id="padding"><input id="password" type="password" name="password" required></td>
               </tr>
            </table>
            <input type="checkbox" name="rememberme" value=""> Ricordami
            <br><br>
            <input type="submit" name="submit" value="Accedi">
            <br>
         </form>
         <a href="./reset_password/request.php">Ho dimenticato la password</a>
         <br>
      </div>
   </body>
</html>


<?php

   // Verifica che tutti i campi siano impostati
   if(isset($_POST["submit"]) && isset($_POST["email"]) && isset($_POST["password"])) {

      // Verifica che la mail inserita sia in un formato corretto
      if(!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
         echo "<p id='error'>La mail inserita non è valida</p>";
      }
      else {
         try {
            $conn = db_connect();

            // ----------------------------------------------------------------
            // Sanitizzazione input
            // ----------------------------------------------------------------
            $email = strtolower(trim($_POST["email"]));
            $pswd = $_POST["password"];
            // ****************************************************************


            // ----------------------------------------------------------------
            // Controlla se l'email inserita è già presente
            // ----------------------------------------------------------------
            $sql = "SELECT id, nome, cognome, email, psw, cod_permesso FROM utenti WHERE email = :email";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":email", $email, PDO::PARAM_STR, 100);
            $stmt->execute();
            $res = $stmt->fetch();
            // ****************************************************************

            if(isset($res["id"])) {

               // Verifica credenziali
               if(password_verify($pswd, $res["psw"])) {
                  // Inizializzazione parametri della sessione
                  $_SESSION["id"] = $res["id"];
                  $_SESSION["nome"] = $res["nome"];
                  $_SESSION["cognome"] = $res["cognome"];
                  $_SESSION["email"] = $res["email"];
                  $_SESSION["cod_permesso"] = $res["cod_permesso"];

                  // ----------------------------------------------------------------
                  // Inizializza cookie per il token dell'utente (Se "Ricordami" è spuntato)
                  // ----------------------------------------------------------------
                  if(isset($_POST["rememberme"])) {
                     $token = token_gen(128);
                     $scadenza = time() + $TIMEOUT_REMEMBER_ME;
                     $token_hash = password_hash($token, PASSWORD_DEFAULT);
                     $ip = $_SERVER['REMOTE_ADDR'];
                     $web_agent = $_SERVER['HTTP_USER_AGENT'];
                     $id = $_SESSION["id"];
                     $giorno_scadenza = date("Y-m-d H:i:s", $scadenza);
                     $selector = "";

                     // Prova a generare il selector per 5 volte
                     // C'è la possibilità di una collisione tra i selector
                     $gen_times = 0;
                     $selector_created = false;
                     while($gen_times < 5) {
                        try {
                           $selector = token_gen(20);

                           $sql = "INSERT autenticazioni (selector, token, ip, web_agent, data_scadenza, cod_utente)
                           VALUES('$selector', '$token_hash', '$ip', '$web_agent', '$giorno_scadenza', $id)";
                           $stmt = $conn->prepare($sql);
                           $stmt->execute();
                           $selector_created = true;
                           break;
                        }
                        catch(PDOException $e) {
                           $gen_times++;
                        }
                     }

                     // Se non è stato possibile creare il selector
                     if(!$selector_created) {
                        die ("<p id='error'>Qualcosa è andato storto</p>");
                     }

                     setcookie("user", "$selector:$token", $scadenza, "/");
                  }
                  // ****************************************************************

                  header("Location:prenotazioni.php");
               } // if(password_verify($pswd, $res["psw"]))
               else {
                  echo "<p id='error'>Credenziali non corrette</p>";
               }
            } // if(isset($res["id"]))
            else {
               echo "<p id='error'>Credenziali non corrette</p>";
            }
         }
         catch(PDOException $e) {
            echo "<p id='error'>Qualcosa è andato storto</p>";
         }
      }
   }

?>
