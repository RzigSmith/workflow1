<?php

class EmailService {
  
 
    // public static function send(string $to, string $subject, string $message): bool {


    //     return true;
    // }

public function send(string $to, string $subject, string $message): void {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'icksanmambote@gmail.com';
        $mail->Password = 'wycpoymwirnpzjmn'; // mot de passe d'application
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        // Expéditeur et destinataire
        $mail->setFrom('icksanmambote@gmail.com', 'Icksan');
        $mail->addAddress($to);

        // Contenu du message
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = '
        <div style="
            font-family: Arial, sans-serif;
            background-color: #f4f6fb;
            padding: 30px;
            max-width: 520px;
            margin: auto;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
        ">
            <div style="text-align: center;">
                <img src="https://cdn-icons-png.flaticon.com/512/845/845646.png" 
                     alt="Succès" width="80" 
                     style="margin-bottom: 20px;"/>
                
                <h2 style="color: #4f46e5; margin-bottom: 10px;">'.$subject.'</h2>
                
                <p style="color: #0f172a; font-size: 16px;">
                    Bonjour <strong style="color: #06b6d4;">'.$email.'</strong>,
                </p>
                
                <p style="color: #64748b; font-size: 15px;">'.$message.'</p>
                
                <div style="
                    background-color: #f8fafc;
                    padding: 15px;
                    margin-top: 20px;
                    border-radius: 8px;
                    color: #10b981;
                    font-weight: bold;
                    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
                ">
                    ✅ Merci pour votre efficacité !
                </div>
                
                <p style="margin-top: 30px; font-size: 14px; color: #94a3b8;">
                    — Workflow
                </p>
            </div>
        </div>';

        $mail->AltBody = $message;

        // Envoi
        $mail->send();
        echo 'Message envoyé avec succès';
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        echo "Erreur lors de l\'envoi : {$mail->ErrorInfo}";
    }
}

}