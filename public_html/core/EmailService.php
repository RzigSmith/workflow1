<?php

class EmailService {
    /**
     * Envoie un e-mail (simulé localement dans un fichier journal en plus d'un envoi réel)
     *
     * @param string $to Destinataire
     * @param string $subject Objet de l'e-mail
     * @param string $message Corps de l'e-mail
     * @return bool
     */
    public static function send(string $to, string $subject, string $message): bool {
        // Chemin du fichier journal d'e-mails
        $uploadDir = __DIR__ . '/../uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $logFile = $uploadDir . '/email_log.txt';

        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "==================================================\n";
        $logEntry .= "Date : $timestamp\n";
        $logEntry .= "À : $to\n";
        $logEntry .= "Objet : $subject\n";
        $logEntry .= "Message :\n$message\n";
        $logEntry .= "==================================================\n\n";

        // Écriture locale dans le journal (pour faciliter le débogage local sur XAMPP)
        file_put_contents($logFile, $logEntry, FILE_APPEND);

        // Tentative d'envoi réel avec mail() (peut échouer si non configuré, d'où le silence @)
        $headers = "From: no-reply@workflow.com\r\n" .
                   "Reply-To: no-reply@workflow.com\r\n" .
                   "Content-Type: text/html; charset=UTF-8\r\n" .
                   "X-Mailer: PHP/" . phpversion();

        @mail($to, $subject, nl2br($message), $headers);

        return true;
    }
}
