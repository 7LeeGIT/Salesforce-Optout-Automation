<?php
// Inclusion du fichier client Salesforce Enterprise
require_once('Force.com-Toolkit-for-PHP/soapclient/SforceEnterpriseClient.php');

// Fonction pour charger les variables d'environnement depuis un fichier .env
function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception("Fichier .env non trouvé");
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Fonction de logging améliorée avec rotation quotidienne des fichiers
function log_message($message, $level = 'INFO') {
    $date = date('Y-m-d');
    $time = date('H:i:s');
    $log_dir = __DIR__ . '/logs';
    $log_file = "$log_dir/app_{$date}.log";
    
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    
    $log_entry = "[$date $time] [$level] $message" . PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// Fonction pour obtenir le dernier ID traité
function getLastProcessedId() {
    $file = __DIR__ . '/last_processed_id.txt';
    $id = file_exists($file) ? trim(file_get_contents($file)) : '0';
    log_message("Dernier ID traité récupéré : $id");
    return $id;
}

// Fonction pour sauvegarder le dernier ID traité
function saveLastProcessedId($id) {
    file_put_contents(__DIR__ . '/last_processed_id.txt', $id);
    log_message("Dernier ID traité sauvegardé : $id");
}

// Fonction pour nettoyer les anciens fichiers de logs
function cleanOldLogs($days = 30) {
    $log_dir = __DIR__ . '/logs';
    $files = glob("$log_dir/*.log");
    $now = time();

    foreach ($files as $file) {
        if (is_file($file)) {
            if ($now - filemtime($file) >= 60 * 60 * 24 * $days) {
                unlink($file);
                log_message("Ancien fichier de log supprimé : " . basename($file));
            }
        }
    }
}

// Fonction principale
function main() {
    // Définir le fuseau horaire
    date_default_timezone_set('Europe/Paris');

    // Nettoyer les anciens logs
    cleanOldLogs();

    // Charger les variables d'environnement
    try {
        loadEnv(__DIR__ . '/.env');
        log_message("Variables d'environnement chargées avec succès");
    } catch (Exception $e) {
        log_message("Échec du chargement des variables d'environnement : " . $e->getMessage(), 'ERREUR');
        return;
    }

    // Récupérer les emails de l'API
    $url = getenv('API_URL');
    $response = file_get_contents($url);

    if ($response === FALSE) {
        log_message("Échec de la récupération des données depuis l'API", 'ERREUR');
        return;
    }

    $data = json_decode($response, true);

    if ($data === NULL) {
        log_message("Échec de l'analyse de la réponse JSON", 'ERREUR');
        return;
    }

    log_message("Données récupérées avec succès depuis l'API");

    $lastProcessedId = getLastProcessedId();
    $newEntries = false;

    $emails = [];
    foreach ($data as $entry) {
        if ($entry[0] > $lastProcessedId) {
            if (isset($entry[1]) && filter_var($entry[1], FILTER_VALIDATE_EMAIL)) {
                $emails[] = $entry[1];
                log_message("Nouvel email à traiter : " . $entry[1]);
            } else {
                log_message("Email invalide ou manquant dans l'entrée : " . json_encode($entry), 'AVERTISSEMENT');
            }
            $newEntries = true;
            $lastProcessedId = $entry[0];
        }
    }

    log_message("Nombre de nouveaux emails à traiter : " . count($emails));

    if ($newEntries) {
        try {
            // Créer une nouvelle instance du client Salesforce
            $mySforceConnection = new SforceEnterpriseClient();
            $wsdl = __DIR__ . '/Force.com-Toolkit-for-PHP/soapclient/enterprise.wsdl.xml';
            $mySforceConnection->createConnection($wsdl);
            log_message("Connexion Salesforce créée");

            $loginResult = $mySforceConnection->login(
                getenv('SALESFORCE_USERNAME'),
                getenv('SALESFORCE_PASSWORD') . getenv('SALESFORCE_SECURITY_TOKEN')
            );
            log_message("Connexion à Salesforce réussie");

            $successCount = 0;
            $failCount = 0;
            $notFoundCount = 0;

            foreach ($emails as $email) {
                log_message("Début du traitement pour l'email : $email");
                $query = "SELECT Id FROM Contact WHERE Email = '$email' LIMIT 1";
                $queryResult = $mySforceConnection->query($query);

                if ($queryResult->size > 0) {
                    $contact = $queryResult->records[0];
                    log_message("Contact trouvé pour l'email $email. ID du contact : " . $contact->Id);
                    
                    $sObject = new stdClass();
                    $sObject->Id = $contact->Id;
                    $sObject->HasOptedOutOfEmail = true;

                    log_message("Tentative de mise à jour du statut opt-out pour le contact : " . $contact->Id);
                    $updateResult = $mySforceConnection->update(array($sObject), 'Contact');

                    if ($updateResult[0]->success) {
                        $successCount++;
                        log_message("Email opt-out réussi pour : $email");
                    } else {
                        $failCount++;
                        log_message("Échec de l'opt-out pour : $email. Raison : " . json_encode($updateResult[0]->errors), 'ERREUR');
                    }
                } else {
                    $notFoundCount++;
                    log_message("Contact non trouvé pour l'email : $email", 'AVERTISSEMENT');
                }
            }

            log_message("Traitement terminé. Succès : $successCount, Échecs : $failCount, Non trouvés : $notFoundCount");

            saveLastProcessedId($lastProcessedId);
        } catch (Exception $e) {
            log_message("Une erreur est survenue lors du traitement : " . $e->getMessage(), 'ERREUR');
            log_message("Trace de la pile : " . $e->getTraceAsString(), 'ERREUR');
        }
    } else {
        log_message("Aucune nouvelle entrée à traiter");
    }
}

// Exécution du script
main();