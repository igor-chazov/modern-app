<?php

/*
 * Форма обратной связи (https://itchief.ru/lessons/php/feedback-form-for-website)
 * Copyright 2016-2023 Alexander Maltsev
 * Licensed under MIT (https://github.com/itchief/feedback-form/blob/master/LICENSE)
 */

header('Content-Type: application/json');

// обработка только ajax запросов (при других запросах завершаем выполнение скрипта)
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {
    exit();
}

// обработка данных, посланных только методом POST (при остальных методах завершаем выполнение скрипта)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit();
}

// имя файла для хранения логов
define('LOG_FILE', 'logs/' . date('Y-m-d') . '.log');
// писать предупреждения и ошибки в лог
const HAS_WRITE_LOG = true;

// отправлять письмо
const HAS_SEND_EMAIL = true;
const EMAIL_SETTINGS = [
    'a'addresses' => ['resume@ooo-modern.ru.'], // кому необходимо отправить письмо
  'from' => ['no-reply@domain.com', 'Имя сайта'], // от какого email и имени необходимо отправить письмо
  'subject' => 'Сообщение с формы обратной связи', // тема письма
  'host' => 'ssl://smtp.yandex.ru', // SMTP-хост
  'username' => 'name@yandex.ru', // SMTP-пользователь
  'password' => '*********', // SMTP-пароль
  'port' => '465' // SMTP-порт
];
const HAS_SEND_NOTIFICATION = false;
const BASE_URL = 'https://domain.com';
const SUBJECT_FOR_CLIENT = 'Ваше сообщение доставлено';
//
const HAS_WRITE_TXT = true;

function itc_log($message)
{
    if (HAS_WRITE_LOG) {
        error_log('Date:  ' . date('d.m.Y h:i:s') . '  |  ' . $message . PHP_EOL, 3, LOG_FILE);
    }
}

$data = [
    'errors' => [],
    'form' => [],
    'logs' => [],
    'result' => 'success'
];

/* 4 ЭТАП - ВАЛИДАЦИЯ ДАННЫХ (ЗНАЧЕНИЙ ПОЛЕЙ ФОРМЫ) */

// валидация темы вопроса
if (!empty($_POST['select_subject'])) {
    $data['form']['select_subject'] = htmlspecialchars($_POST['select_subject']);
} else {
    $data['result'] = 'error';
    $data['errors']['select_subject'] = 'Выберите тему для вопроса.';
    itc_log('Не выбрана тема вопроса.');
}

// валидация name
if (!empty($_POST['name'])) {
    $data['form']['name'] = htmlspecialchars($_POST['name']);
} else {
    $data['result'] = 'error';
    $data['errors']['name'] = 'Заполните это поле.';
    itc_log('Не заполнено поле name.');
}

// валидация email
if (!empty($_POST['email'])) {
    $data['form']['email'] = $_POST['email'];
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $data['result'] = 'error';
        $data['errors']['email'] = 'Email не корректный.';
        itc_log('Email не корректный.');
    }
} else {
    $data['result'] = 'error';
    $data['errors']['email'] = 'Заполните это поле.';
    itc_log('Не заполнено поле email.');
}

// валидация question
if (!empty($_POST['question'])) {
    $data['form']['question'] = htmlspecialchars($_POST['question']);
    if (mb_strlen($data['form']['question'], 'UTF-8') < 20) {
        $data['result'] = 'error';
        $data['errors']['question'] = 'Это поле должно быть не меньше 20 cимволов.';
        itc_log('Поле message должно быть не меньше 20 cимволов.');
    }
} else {
    $data['result'] = 'error';
    $data['errors']['question'] = 'Заполните это поле.';
    itc_log('Не заполнено поле message.');
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

if ($data['result'] == 'success' && HAS_SEND_EMAIL == true) {
    // получаем содержимое email шаблона и заменяем в нём
    $template = file_get_contents(dirname(__FILE__) . '/template/email.tpl');
    $search = ['%subject%', '%select_subject%', '%name%', '%email%', '%question%', '%date%'];
    $replace = [EMAIL_SETTINGS['subject'], $data['form']['select_subject'], $data['form']['name'], $data['form']['email'], $data['form']['question'], date('d.m.Y H:i')];
    $body = str_replace($search, $replace, $template);

    $mail = new PHPMailer(true);
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = function ($str, $level) {
        $file = __DIR__ . '/logs/smtp_' . date('Y-m-d') . '.log';
        file_put_contents($file, gmdate('Y-m-d H:i:s') . "\t$level\t$str\n", FILE_APPEND | LOCK_EX);
    };
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host = EMAIL_SETTINGS['host'];
        $mail->SMTPAuth = true;
        $mail->Username = EMAIL_SETTINGS['username'];
        $mail->Password = EMAIL_SETTINGS['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = EMAIL_SETTINGS['port'];
        //Recipients
        $mail->setFrom(EMAIL_SETTINGS['from'][0], EMAIL_SETTINGS['from'][1]);
        foreach (EMAIL_SETTINGS['addresses'] as $address) {
            $mail->addAddress(trim($address));
        }

        //Content
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->isHTML(true);
        $mail->Subject = EMAIL_SETTINGS['subject'];
        $mail->Body = $body;
        $mail->send();
        itc_log('Форма успешно отправлена.');
    } catch (Exception $e) {
        $data['result'] = 'error';
        itc_log('Ошибка при отправке письма: ' . $mail->ErrorInfo);
    }
}

if ($data['result'] == 'success' && HAS_SEND_NOTIFICATION) {
    // очистка адресов и прикреплёных файлов
    $mail->clearAllRecipients();
    $mail->clearAttachments();
    // получаем содержимое email шаблона и заменяем в нём плейсхолдеры на соответствующие им значения
    $template = file_get_contents(dirname(__FILE__) . '/template/email_client.tpl');
    $search = ['%subject%', '%name%', '%date%'];
    $replace = [SUBJECT_FOR_CLIENT, $data['form']['name'], date('d.m.Y H:i')];
    $body = str_replace($search, $replace, $template);
    try {
        // устанавливаем параметры
        $mail->Subject = SUBJECT_FOR_CLIENT;
        $mail->Body = $body;
        $mail->addAddress($data['form']['email']);
        $mail->send();
        itc_log('Успешно отправлено уведомление пользователю.');
    } catch (Exception $e) {
        itc_log('Ошибка при отправке уведомления пользователю: ' . $mail->ErrorInfo);
    }
}

if ($data['result'] == 'success' && HAS_WRITE_TXT) {
    $output .= 'Email: ' . $data['form']['email'];
    $output = '=======' . date('d.m.Y H:i') . '=======' . PHP_EOL;
    $output .= 'Email: ' . $data['form']['email'] . PHP_EOL;
    $output .= 'Name: ' . $data['form']['name'] . PHP_EOL;
    $output .= 'Question: ' . $data['form']['question'] . PHP_EOL;
    $output .= 'Phone Number: ' . $data['form']['phone'] . PHP_EOL;
    $output .= 'Сообщение: ' . $data['form']['message'] . PHP_EOL;
    $output = '=====================';
    error_log($output, 3, 'logs/forms.log');
}

echo json_encode($data);
exit();
