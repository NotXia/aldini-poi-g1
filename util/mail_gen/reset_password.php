<?php

   require_once (dirname(__FILE__)."/../config.php");

   function reset_psw_mail($id, $nome, $cognome, $email, $data_creazione, $data_modifica) {

      $url_reset = $GLOBALS["URL_RESET_PSW"];

      // ----------------------------------------------------------------
      // Componimento URL per la validazione della mail
      // ----------------------------------------------------------------
      $url_validazione = sprintf(
         $url_reset,
         $id,
         hash("sha512", $nome.$data_creazione),
         hash("sha512", $cognome.$data_creazione),
         hash("sha512", $email.$data_creazione),
         hash("sha512", $data_modifica.$data_creazione)
      );
      // ****************************************************************

      // ----------------------------------------------------------------
      // Componimento HTML della mail da inviare
      // ----------------------------------------------------------------
      $email_format = file_get_contents(dirname(__FILE__)."/templates/reset_password.html");
      $email_format = str_replace("%cognome%", $cognome, $email_format);
      $email_format = str_replace("%nome%", $nome, $email_format);
      $email_format = str_replace("%url_reset%", $url_validazione, $email_format);
      // ****************************************************************

      return $email_format;

   }

   function check_reset_psw_mail($hashes, $values) {
      try {
         return (
            $hashes[0] == hash("sha512", $values[0].$values[3]) &&
            $hashes[1] == hash("sha512", $values[1].$values[3]) &&
            $hashes[2] == hash("sha512", $values[2].$values[3]) &&
            $hashes[3] == hash("sha512", $values[4].$values[3])
         );
      } catch (Exception $e) {
         return false;
      }
   }

?>
