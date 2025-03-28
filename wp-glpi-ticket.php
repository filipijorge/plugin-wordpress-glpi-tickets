<?php
/**
 * Plugin Name: Formulário de Chamados GLPI
 * Description: Plugin para abrir chamados no GLPI a partir do WordPress.
 * Version: 1.0
 * Author: Filipi Jorge
 */

// Bloqueia acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit;
}

// Shortcode para exibir o formulário
function glpi_ticket_form() {
    if (!is_user_logged_in()) {
        return '<p>Você precisa estar logado para abrir um chamado.</p>';
    }
    
    $user = wp_get_current_user();
    $requerente_username = $user->user_login; // Obtém o login do usuário logado

    ob_start();
    ?>
    <h1>Abrir Chamado</h1>
    <form method="post">
        <label>Título:</label><br>
        <input type="text" name="titulo" required><br>
        <label>Descrição:</label><br>
        <textarea name="descricao" required></textarea><br>
        <label>Tipo:</label><br>
        <select name="tipo">
            <option value="1">Incidente</option>
            <option value="2">Requisição</option>
        </select><br><br>
        <button type="submit">Abrir Chamado</button>
    </form>
    <?php
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['titulo']) && !empty($_POST['descricao'])) {
        glpi_create_ticket($_POST['titulo'], $_POST['descricao'], $_POST['tipo'], $requerente_username);
    }
    
    return ob_get_clean();
}
add_shortcode('glpi_form', 'glpi_ticket_form');

function glpi_create_ticket($titulo, $descricao, $tipo, $requerente_username) {
    $glpi_url = "https://chamados.camaraindaiatuba.sp.gov.br/apirest.php";
    $app_token = "dd1dQZsI9WyQcrG4Sb1Kdoma1yOnAVYwETeZCOqi";
    $user_token = "gZgi85G3auvVWbkfxde16u0I1FiEeYSHew1j6W14";
    
    function glpi_request($url, $method = "GET", $headers = [], $data = null) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }
    
    $session_response = glpi_request("$glpi_url/initSession", "GET", [
        "App-Token: $app_token",
        "Authorization: user_token $user_token"
    ]);
    
    if (!isset($session_response["session_token"])) {
        return "Erro ao iniciar sessão no GLPI.";
    }
    
    $session_token = $session_response["session_token"];
    
    $user_search_response = glpi_request("$glpi_url/User/?range=0-500&get_hateoas=0", "GET", [
        "App-Token: $app_token",
        "Session-Token: $session_token",
        "Content-Type: application/json"
    ]);
    
    $requerente_id = null;
    foreach ($user_search_response as $user) {
        if ($user["name"] == $requerente_username) {
            $requerente_id = $user["id"];
            break;
        }
    }
    
    if (!$requerente_id) {
        return "Usuário não encontrado no GLPI.";
    }
    
    $ticket_data = ["input" => [
        "name" => $titulo,
        "content" => $descricao,
        "type" => $tipo,
        "_users_id_requester" => $requerente_id,
        "requesttypes_id" => 8,
    ]];
    
    $ticket_response = glpi_request("$glpi_url/Ticket/", "POST", [
        "App-Token: $app_token",
        "Session-Token: $session_token",
        "Content-Type: application/json"
    ], $ticket_data);
    
    glpi_request("$glpi_url/killSession", "GET", [
        "App-Token: $app_token",
        "Session-Token: $session_token"
    ]);
    
    if (isset($ticket_response['id'])) {
        echo "<p>Chamado aberto com sucesso! ID: " . $ticket_response['id'] . "</p>";
    } else {
        echo "<p>Erro ao abrir chamado.</p>";
    }
}
