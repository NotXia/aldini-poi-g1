<?php
   ob_start();
   session_start();

   require_once (dirname(__FILE__)."/../util/auth_check.php");
   if(!isLogged()) {
      header("Location:../login.php");
   }

   require_once (dirname(__FILE__)."/../util/dbconnect.php");
   require_once (dirname(__FILE__)."/../util/mailer.php");
   require_once (dirname(__FILE__)."/../util/mail_gen/booking_confirm.php");
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
      <link rel="stylesheet" href="../css/admin_navbar.css">
      <link rel="stylesheet" href="../../css/form_table.css">

      <title>Prenotazioni</title>

      <style>
      /* Tooltip container */
      ._tooltip {
         position: relative;
         display: inline-block;
         border-bottom: 1px dotted black; /* If you want dots under the hoverable text */
      }

      /* Tooltip text */
      ._tooltip ._tooltiptext {
         visibility: hidden;
         width: 120px;
         background-color: #555;
         color: #fff;
         text-align: center;
         padding: 5px 0;
         border-radius: 6px;

         /* Position the tooltip text */
         position: absolute;
         z-index: 1;
         bottom: 125%;
         left: 50%;
         margin-left: -60px;

         /* Fade in tooltip */
         opacity: 0;
         transition: opacity 0.3s;
      }

      /* Tooltip arrow */
      ._tooltipa ._tooltiptext::after {
         content: "";
         position: absolute;
         top: 100%;
         left: 50%;
         margin-left: -5px;
         border-width: 5px;
         border-style: solid;
         border-color: #555 transparent transparent transparent;
      }

      /* Show the tooltip text when you mouse over the tooltip container */
      ._tooltip:hover ._tooltiptext {
         visibility: visible;
         opacity: 1;
      }
   </style>

   </head>

   <body>

      <nav class="navbar navbar-dark bg-primary">
         <a class="navbar-brand" href="#">Aldini Valeriani</a>
         <div align="right">

         </div>
      </nav>

      <section id="cover" class="min-vh-90">
         <div id="cover-caption">
            <div class="container">
               <div class="row text-black">
                  <div class="col-xl-8 col-lg-8 col-md-10 col-sm-12 mx-auto text-center form p-4">
                     <h1 class="display-4 py-2">Prenotazioni</h1><br>

                     <div class="table-responsive-lg" align="center">
                        <table class="table table-bordered">
                           <tr style='text-align:center;'>
                              <th>Data</th> <th>Orario</th>
                           </tr>
                           <form action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" method="POST">
                              <?php
                                 try {
                                    $conn = db_connect();
                                    $sql = "SELECT *
                                            FROM visite
                                            WHERE id NOT IN(SELECT cod_visita FROM prenotazioni WHERE cod_utente = :cod_utente) AND
                                            data_inizio+ora_inizio >= NOW() AND
                                            posti_disponibili > 0";
                                    $stmt = $conn->prepare($sql);
                                    $stmt->bindParam(":cod_utente", $_SESSION["id"], PDO::PARAM_INT);
                                    $stmt->execute();

                                    $res = $stmt->fetchAll();

                                    if(!empty($res)) {
                                       foreach($res as $row) {
                                          $id = $row["id"];
                                          $data = date("d/m/Y", strtotime($row["data_inizio"]));
                                          $orario = date("H:i", strtotime($row["ora_inizio"])) . " - " . date("H:i", strtotime($row["ora_fine"]));
                                          echo "<tr style='text-align:center;'>";
                                          echo "<td>$data</td> <td>$orario</td> <td><input class='btn btn-primary btn-sm' type='submit' name='book[$id]' value='Prenotati'></td>";
                                          echo "</tr>";
                                       }
                                    }
                                 } catch (PDOException $e) {
                                    echo "<p id='error'>Si è verificato un errore</p>";
                                 }

                              ?>

                           </table>
                           <input id="device" type="checkbox" name="device">
                           <label for="device">
                              <div class="_tooltip">Richiedi dispositivo
                                 <span class="_tooltiptext">Ti verrà data conferma via mail se disponibile o meno</span>
                              </div>
                           </label>
                           <p>DESCRIZIONE DI COSA È IL DISPOSITIVO</p>
                        </form>
                     </div>

                  </div>
               </div>
            </div>
         </div>
      </section>

   </body>
</html>

<?php

   if(isset($_POST["book"])) {

      // Prende l'id della visita corrispondente al bottone premuto
      $id_visita = 0;
      foreach($_POST["book"] as $id=>$x) {
         $id_visita = $id;
         break;
      }

      try {
         $conn = db_connect();

         $conn->beginTransaction();

         // Controlla che la visita sia valida
         $sql = "SELECT *
                 FROM visite
                 WHERE id NOT IN(SELECT cod_visita FROM prenotazioni WHERE cod_utente = :cod_utente) AND
                       id = :id_visita_form AND
                       data_inizio+ora_inizio >= NOW() AND
                       posti_disponibili > 0";
         $stmt = $conn->prepare($sql);
         $stmt->bindParam(":cod_utente", $_SESSION["id"], PDO::PARAM_INT);
         $stmt->bindParam(":id_visita_form", $id_visita, PDO::PARAM_INT);
         $stmt->execute();

         $res_visita = $stmt->fetch();
         // È possibile prenotare
         if(!empty($res_visita)) {

            $id_dispositivo = null;

            // Se l'utente chiede il dispositivo, verifica la disponibilità
            if(isset($_POST["device"])) {
               $sql = "SELECT id FROM dispositivi WHERE
                       id NOT IN(SELECT dispositivi.id
                                 FROM prenotazioni, dispositivi
                                 WHERE cod_dispositivo = dispositivi.id AND
                                       cod_visita = :cod_visita)";
               $stmt = $conn->prepare($sql);
               $stmt->bindParam(":cod_visita", $id_visita, PDO::PARAM_INT);
               $stmt->execute();

               $res_dispositivi = $stmt->fetch();

               if(!empty($res_dispositivi)) {
                  $id_dispositivo = $res_dispositivi["id"];
               }
            }

            $sql = "INSERT prenotazioni (cod_utente, cod_visita, cod_permesso, cod_dispositivo)
                    VALUES (:cod_utente, :cod_visita, 2, :cod_dispositivo)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":cod_utente", $_SESSION["id"], PDO::PARAM_INT);
            $stmt->bindParam(":cod_visita", $id_visita, PDO::PARAM_INT);
            $stmt->bindParam(":cod_dispositivo", $id_dispositivo);
            $stmt->execute();

            $sql = "UPDATE visite
                    SET posti_disponibili = posti_disponibili-1
                    WHERE id = :id_visita";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":id_visita", $id_visita, PDO::PARAM_INT);
            $stmt->execute();

            $conn->commit();

            mailTo(
               $_SESSION["email"],
               "POI - Conferma prenotazione",
               booking_confirm_mail(
                  $_SESSION["nome"],
                  $_SESSION["cognome"],
                  date("d/m/Y",strtotime($res_visita["data_inizio"])),
                  date("H:i", strtotime($res_visita["ora_inizio"])),
                  date("H:i", strtotime($res_visita["ora_fine"])),
                  ($id_dispositivo != null)
               )
            );

            header("Location: view.php");
         }

      } catch (PDOException $e) {
         echo $e->getMessage();
         $conn->rollBack();
      }


   }

?>
