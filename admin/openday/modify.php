<?php
   ob_start();
   require_once (dirname(__FILE__)."/../../util/auth_check.php");
   if(isLogged()) {
      if($_SESSION["cod_permesso"] != 3) {
         header("Location:../../index.php");
      }
   }
   else {
      header("Location:../login.php");
   }

   require_once (dirname(__FILE__)."/../../util/dbconnect.php");
   require_once (dirname(__FILE__)."/../../util/config.php");
   require_once (dirname(__FILE__)."/../../util/mail_gen/openday_update.php");
   require_once (dirname(__FILE__)."/../../util/mailer.php");
?>

<!DOCTYPE html>
<html>
   <head>

      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
      <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
      <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
      <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
      <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/css/bootstrap-select.min.css">
      <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/js/bootstrap-select.min.js"></script>
      <link rel="stylesheet" href="../../css/navbar.css">
      <link rel="stylesheet" href="../../css/form_table.css">

      <title>Modifica</title>

   </head>

   <body>

      <nav class="navbar navbar-dark bg-primary">
         <div class="navbar-brand">
            <a class="navbar-brand nav_nopadding" href="../index.php">
               <img class="nav_logo" src="../../res/logo.png" alt="AV Logo">
               AV Admin
            </a>
         </div>
         <div align="right">
            <a class="nav_options" href="../index.php">Dashboard</a>
            <a class="nav_options" href="view.php">Open Day</a>
            <a class="nav_options" href="../labo/view.php">Laboratori</a>
            <a class="nav_options" href="../devices/view.php">Dispositivi</a>
            <a class="nav_options" href="../logout.php">Logout</a>
         </div>
      </nav>

      <section id="cover" class="min-vh-100">
         <div id="cover-caption">
            <div class="container">
               <div class="row text-black">
                  <div class="col-xl-6 col-lg-8 col-md-10 col-sm-12 mx-auto text-center form p-4">
                     <h1 class="display-4 py-2">Modifica Open Day</h1>

                     <div class="table-responsive" align="center">
                        <?php
                           if(empty($_GET["id"]) && empty($_POST["id"])) {
                              ?>
                              <h3>Errore</h3>
                              <?php
                           }
                           else {
                              if(!empty($_GET["id"])) { $id = htmlentities($_GET["id"]); }
                              else { $id = htmlentities($_POST["id"]); }

                              ?>
                              <form action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" method="POST">
                                 <input type="hidden" name="id" value="<?php echo $id ?>">
                                 <?php
                                    $conn = db_connect();
                                    $sql = "SELECT * FROM visite WHERE id = :id";
                                    $stmt = $conn->prepare($sql);
                                    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                                    $stmt->execute();
                                    $res = $stmt->fetch();
                                    if(empty($res)) {
                                       die ("<h3>Errore</h3>");
                                    }
                                 ?>
                                 <table>
                                    <tr>
                                       <td id="label">Data</td>
                                       <td id="padding"><input type="date" name="data" min="<?php echo htmlentities(date("Y-m-d")); ?>" value="<?php if(!empty($res['data_inizio'])) echo $res['data_inizio']; ?>" required></td>
                                    </tr>
                                    <tr>
                                       <td id="label">Ora inizio</td>
                                       <td id="padding"><input type="time" name="ora_inizio" value="<?php if(!empty($res['ora_inizio'])) echo $res['ora_inizio']; ?>" required></td>
                                    </tr>
                                    <tr>
                                       <td id="label">Ora fine</td>
                                       <td id="padding"><input type="time" name="ora_fine" value="<?php if(!empty($res['ora_fine'])) echo $res['ora_fine']; ?>" required></td>
                                    </tr>
                                 </table>
                                 <br>
                                 <input type="checkbox" id="send_mail" name="send_mail"><label for="send_mail">&nbspManda un avviso a chi si è prenotato</label>
                                 <br><br>
                                 <input type="submit" name="confirm" value="Salva">
                              </form>

                           <?php
                           }
                           ?>
                     </div>

                  </div>
               </div>
            </div>
         </div>
      </section>

   </body>
</html>

<?php

   if(isset($_POST["confirm"])) {

      // Controlla che tutti i campi siano impostati
      if(!empty($_POST["data"]) && !empty($_POST["ora_inizio"]) && !empty($_POST["ora_fine"]) && !empty($_POST["id"])) {

         // Verifica la validità della data e della fascia oraria
         if(strtotime($_POST["data"]) >= strtotime(date("Y-m-d")) && strtotime($_POST["ora_fine"]) > strtotime($_POST["ora_inizio"])) {
            try {
               $conn = db_connect();

               // Estrare i dati originali della visita
               $sql = "SELECT * FROM visite WHERE id = :id";
               $stmt = $conn->prepare($sql);
               $stmt->bindParam(":id", $_POST["id"], PDO::PARAM_INT);
               $stmt->execute();
               $res_old = $stmt->fetch();

               if(!empty($res_old)) {
                  $sql = "UPDATE visite SET data_inizio = :data_inizio, ora_inizio = :ora_inizio, ora_fine = :ora_fine
                  WHERE id = :id";
                  $stmt = $conn->prepare($sql);
                  $stmt->bindParam(":data_inizio", $_POST["data"]);
                  $stmt->bindParam(":ora_inizio", $_POST["ora_inizio"]);
                  $stmt->bindParam(":ora_fine", $_POST["ora_fine"]);
                  $stmt->bindParam(":id", $_POST["id"], PDO::PARAM_INT);
                  $stmt->execute();

                  // Invio mail di avviso agli utenti già prenotati
                  if(isset($_POST["send_mail"])) {
                     $sql = "SELECT email, nome, cognome FROM prenotazioni, utenti
                     WHERE cod_utente = utenti.id AND
                     cod_visita = :cod_visita";
                     $stmt = $conn->prepare($sql);
                     $stmt->bindParam(":cod_visita", $_POST["id"], PDO::PARAM_INT);
                     $stmt->execute();
                     $res = $stmt->fetchAll();

                     if(!empty($res)) {
                        foreach($res as $row) {
                           mailTo(
                              $row["email"],
                              "POI - Modifiche prenotazione",
                              openday_update_mail(
                                 $row["nome"], $row["cognome"],
                                 date("d/m/Y", strtotime($res_old["data_inizio"])),
                                 date("H:i", strtotime($res_old["ora_inizio"])),
                                 date("H:i", strtotime($res_old["ora_fine"])),
                                 date("d/m/Y", strtotime($_POST["data"])),
                                 date("H:i", strtotime($_POST["ora_inizio"])),
                                 date("H:i", strtotime($_POST["ora_fine"]))
                              )
                           );
                        }
                     }
                  }

                  header("Location:view.php");
               }

            } catch (PDOException $e) {
               echo $e->getMessage();
            }
         } // if(strtotime($_POST["data"]) >= strtotime(date("Y-m-d")) && strtotime($_POST["ora_fine"]) > strtotime($_POST["ora_inizio"]))
         else {
            echo "<p id='error'>Data o ora non corrette</p>";
         }
      } // if(!empty($_POST["data"]) && !empty($_POST["ora_inizio"]) && !empty($_POST["ora_fine"]) && !empty($_POST["id"]))
   }

?>
