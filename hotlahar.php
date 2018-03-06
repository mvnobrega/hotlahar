<?php


// Informações da LAHAR
$token_api_lahar = 'TOKEN_API_LAHAR_OBTIDO_EM_CONFIGURACOES'
$endpoint = 'TIPO_DE_INTEGRACAO'
$nome_formulario = 'NOME_DO_IDENTIFICADOR_DA_PAGINA'

// Insira as informações vinda do Hotmart
$hotmart_token      = "TOKEN HOTMART";
$hotmart_id_prod    = "ID Produto Hotmart";
$hotmart_id_test    = "1439";//1439 é o ID do produto usado para teste de API pelo Hotmart

// Informações recebidas via post do Hotmart
$hotmart_first_name = $_POST['first_name'];
$hotmart_last_name  = $_POST['last_name'];
$hotmart_mail       = $_POST['email'];
$hotmart_prod       = $_POST['prod'];
$hottok             = $_POST['hottok'];

// Log de requisição para análise
log_message('*** Requisição Hotmautic ***');
log_message("Nome: $hotmart_first_name");
log_message("Sobrenome: $hotmart_last_name");
log_message("E-mail: $hotmart_mail");
log_message("Produto: $hotmart_prod");
log_message("Status: $status");

//Validação das informações e tomada da ação para cada status recebido
if ($hottok == $hotmart_token && ($hotmart_prod == $hotmart_id_prod) || ($hotmart_prod == $hotmart_id_test)) {
  switch ($status) {
    case 'approved':
      // Campos a enviar para a lahar
    $campos = array(
        'email_contato' => $hotmart_mail
        'tags' => 'Hotmart - Aprovado'
      );
      integra_api_lahar($token_api_lahar, $endpoint, $nome_formulario, $campos)
      break;

    case 'refunded':
    case 'chargeback':
    case 'dispute':
    // Campos a enviar para a lahar
    $campos = array(
        'email_contato' => $hotmart_mail
        'tags' => 'Hotmart - Cancelado'
      );
      integra_api_lahar($token_api_lahar, $endpoint, $nome_formulario, $campos)
      break;

    default:
      log_message("Status não tratado: $status");
      break;
  }
}
else
{
  log_message("Chamada inválida - Acesso não liberado");
}


// API da LAHAR
function integra_api_lahar($token_api_lahar, $endpoint, $nome_formulario, $campos) {
  $endpoint_full_url = 'https://app.lahar.com.br/api/'.$endpoint;
  if ($endpoint == 'conversions') {
    $method = 'POST';
  }
  if ($endpoint == 'leads') {
    $method = 'PUT';
  }
  try {
    if (!array_key_exists('token_api_lahar',$campos)) {
      $campos['token_api_lahar'] = $token_api_lahar;
    }
    else if ($campos['token_api_lahar'] == NULL) {
      $campos['token_api_lahar'] = $token_api_lahar;
    }
    if (!array_key_exists('nome_formulario',$campos)) {
      $campos['nome_formulario'] = $nome_formulario;
    }
    else if ($campos['nome_formulario'] == NULL) {
      $campos['nome_formulario'] = $nome_formulario;
    }
    if (!array_key_exists('url_origem',$campos)) {
      $campos['url_origem'] = 'integracao-javascript';
    }
    else if ($campos['url_origem'] == NULL) {
      $campos['url_origem'] = 'integracao-javascript';
    }
    $post_fields = http_build_query($campos);
    if ($method == 'POST') {
      $ch = curl_init($endpoint_full_url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_POST, TRUE);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      $curl_response = curl_exec($ch);
      curl_close($ch);
      $json = json_decode($curl_response);
      $retorno = $json;
    }
    else if ($method == 'PUT') {
      $ch = curl_init($endpoint_full_url);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
      curl_setopt($ch, CURLOPT_HEADER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($campos));
      $curl_response = curl_exec($ch);
      curl_close($ch);
      $json = json_decode($curl_response);
      $retorno = $json;
    }
  }
  catch (Exception $e) {
    $retorno = array(
        'status' => 'erro',
        'data' => array(
            'error' => array(
                'code' => 404,
                'message' => 'Erro imprevisto.'
            )
        )
    );
  }
  return $retorno;
}

function log_message($message) {
  $log_file = './hotmautic.log';

  $current_date = date('r');
  $log_messsage = "$current_date - $message\n";

  echo($log_messsage);
  file_put_contents($log_file, $log_messsage, FILE_APPEND);
}