<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\apianalytic;

use Drupal\Component\Utility\Xss;
use Drupal\ibm_apim\Rest\Payload\RestResponseReader;
use Drupal\user\Controller\UserController;

class RestAnalytic implements RestAnalyticInterface
{

  /**
   * @param string $url
   * @param string $auth
   * @param bool $gettingConfig
   * @param bool $messageErrors
   * @param bool $returnResult
   *
   * @return \stdClass|null
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public static function get($url, $auth = 'user', $gettingConfig = FALSE, $messageErrors = TRUE, $returnResult = FALSE): ?\stdClass
  {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $url);
    $returnValue = NULL;
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return self::call_base($url, 'GET', $auth, NULL, $messageErrors, $returnResult, $gettingConfig);
  }

  /**
   * @param string $url
   * @param string $auth
   * @param bool $gettingConfig
   * @param bool $messageErrors
   * @param bool $returnResult
   *
   * @return \stdClass|null
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public static function raw($url, $auth = 'user', $gettingConfig = FALSE, $messageErrors = TRUE, $returnResult = TRUE): ?\stdClass
  {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $url);
    $returnValue = NULL;
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return self::call_base($url, 'GET', $auth, NULL, $messageErrors, $returnResult, $gettingConfig);
  }


  /**
   * @param string $url
   * @param string $data
   * @param string $auth
   *
   * @return \stdClass|null
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public static function post($url, $data, $auth = 'user'): ?\stdClass
  {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $url);
    $returnValue = NULL;
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return self::call_base($url, 'POST', $auth, $data);
  }

  /**
   * @param string $url
   * @param string $data
   * @param string $auth
   *
   * @return \stdClass|null
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public static function put($url, $data, $auth = 'user'): ?\stdClass
  {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $url);
    $returnValue = NULL;
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return self::call_base($url, 'PUT', $auth, $data);
  }

  /**
   * @param string $url
   * @param string $data
   * @param string $auth
   * @param bool $messageErrors
   * @param bool $returnResult
   *
   * @return \stdClass|null
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public static function patch($url, $data, $auth = 'user', $messageErrors = TRUE, $returnResult = FALSE): ?\stdClass
  {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $url);
    $returnValue = NULL;
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return self::call_base($url, 'PATCH', $auth, $data, $messageErrors, $returnResult, FALSE);
  }

  /**
   * @param string $url
   * @param string $auth
   *
   * @return \stdClass|null
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public static function delete($url, $auth = 'user'): ?\stdClass
  {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $url);
    $returnValue = NULL;
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return self::call_base($url, 'DELETE', $auth);
  }

  /**
   * @param $url
   * @param string $verb
   * @param null $headers
   * @param null $data
   * @param bool $returnResult
   * @param null $insecure
   * @param null $providedCertificate
   * @param bool $notifyDrupal
   *
   * @return \stdClass|null
   * @throws \Exception
   */
  public static function json_http_request($url, $verb = 'GET', $headers = NULL, $data = NULL, $returnResult = FALSE, $insecure = NULL, $providedCertificate = NULL, $notifyDrupal = TRUE): ?\stdClass
  {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, [$url, $verb]);
    }
    $apim_rest_trace = FALSE;
    if (mb_strpos($url, 'https://') !== 0) {
      $siteConfig = \Drupal::service('ibm_apim.site_config');

      $hostPieces = $siteConfig->parseApimHost();
      if (isset($hostPieces['url'])) {
        $url = $hostPieces['url'] . $url;
      } else {
        if ($notifyDrupal) {
          drupal_set_message(t('APIC Hostname not set. Aborting'), 'error');
        }
        return NULL;
      }
    }

    // remove any double /consumer-api calls
    if (mb_strpos($url, '/consumer-api/consumer-api') !== 0) {
      $url = str_replace('/consumer-api/consumer-api', '/consumer-api', $url);
    }

    // Use curl instead of drupal_http_request so that we can
    // check the server certificates are genuine so that we
    // do not fall foul of a man-in-the-middle attack.
    $resource = curl_init();

    curl_setopt($resource, CURLOPT_URL, $url);
    if ($headers !== NULL) {
      curl_setopt($resource, CURLOPT_HTTPHEADER, $headers);
    }
    curl_setopt($resource, CURLOPT_RETURNTRANSFER, 1);

    // Return the response header as part of the response
    curl_setopt($resource, CURLOPT_HEADER, 1);

    // proxy settings
    if (\Drupal::hasContainer()) {
      $use_proxy = (bool) \Drupal::config('ibm_apim.settings')->get('use_proxy');
      if ($use_proxy === TRUE) {
        $proxy_url = \Drupal::config('ibm_apim.settings')->get('proxy_url');
        if ($proxy_url !== NULL && !empty($proxy_url)) {
          curl_setopt($resource, CURLOPT_PROXY, $proxy_url);
          $proxy_type = \Drupal::config('ibm_apim.settings')->get('proxy_type');
          if ($proxy_type !== NULL) {
            curl_setopt($resource, CURLOPT_PROXYTYPE, $proxy_type);
          }
          $proxy_auth = \Drupal::config('ibm_apim.settings')->get('proxy_auth');
          if ($proxy_auth !== NULL && !empty($proxy_auth)) {
            curl_setopt($resource, CURLOPT_PROXYUSERPWD, $proxy_auth);
          }
          curl_setopt($resource, CURLOPT_FOLLOWLOCATION, TRUE);
          $apim_rest_trace = (bool) \Drupal::config('ibm_apim.settings')->get('apim_rest_trace');
          if ($apim_rest_trace === TRUE) {
            \Drupal::logger('ibm_apim_rest')->debug('Proxy URL: %data', ['%data' => $proxy_url]);
          }
        }
      }
    }

    if ($verb !== 'GET') {
      curl_setopt($resource, CURLOPT_CUSTOMREQUEST, $verb);
    }

    if (($verb === 'PUT' || $verb === 'POST' || $verb === 'PATCH') && isset($data)) {
      curl_setopt($resource, CURLOPT_POSTFIELDS, $data);
    }
    if ($verb === 'HEAD') {
      curl_setopt($resource, CURLOPT_NOBODY, TRUE);
      curl_setopt($resource, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    }
    if (\Drupal::hasContainer()) {
      // set a custom UA string for the portal
      $apicVersion = \Drupal::state()->get('ibm_apim.version');
      $hostname = gethostname();
      if (!isset($hostname)) {
        $hostname = '';
      }
      curl_setopt($resource, CURLOPT_USERAGENT, 'IBM API Connect Developer Portal/' . $apicVersion['value'] . ' (' . $apicVersion['description'] . ') ' . $hostname);
    }

    // Enable auto-accept of self-signed certificates if this
    // has been set in the module config by an admin.
    self::curl_set_accept_ssl($resource, $insecure, $providedCertificate);

    if (\Drupal::hasContainer()) {
      $apim_rest_trace = (bool) \Drupal::config('ibm_apim.settings')->get('apim_rest_trace');
      if ($apim_rest_trace === TRUE) {
        curl_setopt($resource, CURLOPT_VERBOSE, TRUE);
        \Drupal::logger('ibm_apim_rest')->debug('Payload: %data', ['%data' => serialize($data)]);
      }
    }

    $response = curl_exec($resource);
    $http_status = curl_getinfo($resource, CURLINFO_HTTP_CODE);
    $error = curl_error($resource);

    // Construct the result object we expect
    $result = new \stdClass();

    // Assign the response headers
    $header_size = curl_getinfo($resource, CURLINFO_HEADER_SIZE);
    $header_txt = mb_substr($response, 0, $header_size);
    $result->headers = [];

    foreach (explode("\r\n", $header_txt) as $line) {
      $parts = explode(': ', $line);
      if (count($parts) === 2) {
        $result->headers[$parts[0]] = $parts[1];
      }
    }

    if ($error && $http_status === 0) {
      // a return code of zero mostly likely means there has been a certificate error
      // so make sure we surface this in the UI
      if ($notifyDrupal) {
        drupal_set_message(t('Could not communicate with server. Reason: ') . serialize($error), 'error');
        \Drupal::logger('ibm_apim')->error('Failed to communicate with remote server. URL was @url. Error was @error', [
          '@url' => $url,
          '@error' => $error,
        ]);
      } else {
        throw new \Exception('Could not communicate with server. Reason: ' . $error);
      }
    }

    $result->data = mb_substr($response, $header_size);

    $result->code = $http_status;

    curl_close($resource);

    if (!$returnResult && $result->data !== '') {
      if (empty($headers) || !in_array('Accept: application/vnd.ibm-apim.swagger2+yaml', $headers, FALSE)) {
        $result->data = self::get_json($result->data);
      }
    }
    if ($apim_rest_trace === TRUE && \Drupal::hasContainer()) {
      \Drupal::logger('ibm_apim_rest')->debug('REST Trace output: %data.', [
        '%data' => serialize($result),
      ]);
    }
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    return $result;
  }

  /**
   * Turns a string of JSON into a PHP object.
   *
   * @param $string
   *
   * @return array
   */
  private static function get_json($string): array
  {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    $decoded = json_decode($string, TRUE);
    // handle not being fed valid JSON
    if ($decoded === NULL) {
      $decoded = [
        'content' => $string,
        'json_last_error' => json_last_error(),
        'json_last_error_msg' => json_last_error_msg(),
        'errors' => ['json.parse.error' => 'JSON parse error'],
      ];
    }
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    return $decoded;
  }

  /**
   * If the developer mode config parameter is true then sets options
   * on a curl resource to enable auto-accept of self-signed
   * certificates.
   *
   * @param $resource
   * @param null $insecure
   * @param null $providedCertificate
   */
  private static function curl_set_accept_ssl($resource, $insecure = NULL, $providedCertificate = NULL): void
  {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    // Always set the defaults first
    curl_setopt($resource, CURLOPT_SSL_VERIFYPEER, TRUE);
    curl_setopt($resource, CURLOPT_SSL_VERIFYHOST, 2);

    if ($insecure === NULL) {
      $insecure = \Drupal::state()->get('ibm_apim.insecure');
    }

    // TODO force insecure to true until we've sorted out certs
    $insecure = TRUE;

    if ($insecure) {
      curl_setopt($resource, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($resource, CURLOPT_SSL_VERIFYHOST, 0);
    } else {
      if ($providedCertificate === NULL) {
        $providedCertificate = \Drupal::state()->get('ibm_apim.provided_certificate');
      } elseif ($providedCertificate === 'Default_CA') {
        $providedCertificate = NULL;
      }

      if ($providedCertificate) {
        // Tell curl to use the certificate the user provided
        curl_setopt($resource, CURLOPT_CAINFO, '/etc/apim.crt');
        if ($providedCertificate === 'mismatch') {
          // If the certificate is does not contain the correct server name
          // then tell curl to accept it anyway. The user gets a warning when
          // they provide a certificate like this so they understand this is
          // less secure than using a certificate with a matching server name.
          curl_setopt($resource, CURLOPT_SSL_VERIFYHOST, 0);
        }
      }
    }
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
  }

  /**
   * Where the real work to make a call to the IBM APIm API is done.
   *
   * @param $url
   *          The IBM APIm API URL
   *
   * @param $verb
   *          The HTTP verb to use, must be in the list: GET, PUT, DELETE, POST
   *
   * @param $auth
   *          The authorization string to use, the default is the current user. Other
   *          options are:
   *          clientid - which will use the catalog's client ID header
   *          admin - which will use the admin user registered in the module configuration settings
   *          NULL - use no authorization
   *          any other value - will be included in the Authorization: Basic header as is.
   *
   * @param $data
   *          A string containing the JSON data to submit to the IBM API
   *
   * @param bool $messageErrors
   *          Should the function log errors?
   *
   * @param bool $returnResult
   *          Normally only the result data is returned, if set to TRUE the entire
   *          result object will be returned.
   * @param bool $gettingConfig
   *
   * @return \stdClass|null
   * @throws \Exception
   */
  private static function call_base($url, $verb, $auth = 'user', $data = NULL, $messageErrors = TRUE, $returnResult = FALSE, $gettingConfig = FALSE): ?\stdClass
  {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, [$url, $verb]);
    $utils = \Drupal::service('ibm_apim.utils');
    $session_store = \Drupal::service('tempstore.private')->get('ibm_apim');
    $site_config = \Drupal::service('ibm_apim.site_config');

    $returnValue = NULL;
    if (mb_strpos($url, 'https://') !== 0) {
      $hostPieces = $site_config->parseApimHost();
      if (isset($hostPieces['url'])) {
        $url = $hostPieces['url'] . $url;
      } else {
        drupal_set_message(t('APIC Hostname not set. Aborting'), 'error');
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
        return NULL;
      }
    }
    // remove any double /consumer-api calls
    if (mb_strpos($url, '/consumer-api/consumer-api') !== 0) {
      $url = str_replace('/consumer-api/consumer-api', '/consumer-api', $url);
    }

    $headers = [
      'Content-Type: application/json',
      'Accept: application/json',
    ];
    $lang_name = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $lang_name = $utils->convert_lang_name($lang_name);
    if (isset($lang_name)) {
      $headers[] = 'Accept-Language: ' . $lang_name;
    }


    if ($gettingConfig === FALSE) {
      $headers[] = 'X-IBM-Consumer-Context: ' . $site_config->getOrgId() . '.' . $site_config->getEnvId();
    }

    if ($auth === 'user') {
      $bearer_token = $session_store->get('auth');

      if (isset($bearer_token)) {
        $headers[] = 'Authorization: Bearer ' . $bearer_token;
      }
    } elseif ($auth === 'clientid') {
      $headers[] = 'X-IBM-Client-Id: ' . $site_config->getClientId();
      $headers[] = 'X-IBM-Client-Secret: ' . $site_config->getClientSecret();
    } elseif ($auth === 'platform') {
      $apiToken = \Drupal::state()->get('ibm_apic_mail.token');
      if ($apiToken === NULL || !isset($apiToken['access_token'], $apiToken['expires_in']) || $apiToken['expires_in'] < time()) {
        $apiToken = self::getPlatformToken();
      }
      if ($apiToken !== NULL && isset($apiToken['access_token'])) {
        $headers[] = 'Authorization: Bearer ' . $apiToken['access_token'];
      }
    } elseif ($auth !== NULL) {
      $headers[] = 'Authorization: Bearer ' . $auth;
    }

    $secs = time();
    \Drupal::logger('ibm_apim')->info('call_base: START: %verb %url', [
      '%verb' => $verb,
      '%url' => $url,
    ]);

    $result = self::json_http_request($url, $verb, $headers, $data, $returnResult);

    $secs = time() - $secs;
    if ($result !== NULL) {
      \Drupal::logger('ibm_apim')->info('call_base: %secs secs duration. END: %verb %url %code', [
        '%secs' => $secs,
        '%verb' => $verb,
        '%url' => $url,
        '%code' => $result->code,
      ]);
    }
    $expires_in = $session_store->get('expires_in');
    if ($gettingConfig && isset($result) && (int) $result->code === 204) {
      $result->data = NULL;
      $returnValue = $result;
    } elseif (isset($result) && (int) $result->code >= 200 && (int) $result->code < 300) {
      if ($returnResult !== TRUE) {
        $returnValue = $result;
      }
    } elseif (isset($result) && (int) $result->code === 401 && $expires_in !== NULL && (int) $expires_in < time()) {
      // handle token having expired
      // force log the user out, they can login and try again
      drupal_set_message(t('Session expired. Please sign in again.'), 'error');
      user_logout();
    } elseif ($messageErrors) {
      if ($returnResult) {
        // Need to convert to json if return_result was true as json_http_request()
        // will not have done it
        $result->data = self::get_json($result->data);
      }
      $response_reader = new RestResponseReader();
      $json_result = $response_reader->read($result);
      if ($json_result !== NULL) {
        $errors = $json_result->getErrors();
        if ($errors) {
          foreach ($errors as $error) {
            drupal_set_message(Xss::filter($error), 'error');
            $returnValue = $result;
          }
        }
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    if ($returnResult) {
      return $result;
    }
    return $returnValue;
  }

  public static function callanalyticserver($url, $data = NULL, $extraHeaders = NULL, $mutualAuth = NULL)
  {
    $returnValue = NULL;

    $utils = \Drupal::service('ibm_apim.utils');
    $session_store = \Drupal::service('tempstore.private')->get('ibm_apim');
    $config = \Drupal::service('ibm_apim.site_config');

    if (empty($url)) {
      drupal_set_message(t('URL not specified. Specify a valid URL and try again.'), 'error');
      return NULL;
    }
    if (mb_strpos($url, 'https://') !== 0) {
      $siteConfig = \Drupal::service('ibm_apim.site_config');

      $hostPieces = $siteConfig->parseApimHost();
      if (isset($hostPieces['url'])) {
        $url = $hostPieces['url'] . $url;
      } else {
        drupal_set_message(t('APIC Hostname not set. Aborting'), 'error');
        return NULL;
      }
    }

    // remove any double /consumer-api calls
    if (mb_strpos($url, '/consumer-api/consumer-api') !== 0) {
      $url = str_replace('/consumer-api/consumer-api', '/consumer-api', $url);
    }

    $ch = curl_init($url);

    $headers = [];
    $headers[] = 'X-IBM-Consumer-Context: ' . $config->getOrgId() . '.' . $config->getEnvId();
    $lang_name = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $lang_name = $utils->convert_lang_name($lang_name);
    if (isset($lang_name)) {
      $headers[] = 'Accept-Language: ' . $lang_name;
    }

    $bearer_token = $session_store->get('auth');
    if (isset($bearer_token)) {
      $headers[] = 'Authorization: Bearer ' . $bearer_token;
    }
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    $headers[] = 'Content-Type: application/json';
    if (isset($extraHeaders)) {
      foreach ($extraHeaders as $key => $value) {
        $headers[] = $value;
      }
    }
    if (\Drupal::hasContainer()) {
      $use_proxy = (bool) \Drupal::config('ibm_apim.settings')->get('use_proxy');
      if ($use_proxy === TRUE) {
        $proxy_url = \Drupal::config('ibm_apim.settings')->get('proxy_url');
        if ($proxy_url !== NULL && !empty($proxy_url)) {
          curl_setopt($ch, CURLOPT_PROXY, $proxy_url);
          $proxy_type = \Drupal::config('ibm_apim.settings')->get('proxy_type');
          if ($proxy_type !== NULL) {
            curl_setopt($ch, CURLOPT_PROXYTYPE, $proxy_type);
          }
          $proxy_auth = \Drupal::config('ibm_apim.settings')->get('proxy_auth');
          if ($proxy_auth !== NULL && !empty($proxy_auth)) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_auth);
          }
        }
      }
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    // set a custom UA string for the portal
    $apicVersion = \Drupal::state()->get('ibm_apim.version');
    $hostname = gethostname();
    if (!isset($hostname)) {
      $hostname = '';
    }
    curl_setopt($ch, CURLOPT_USERAGENT, 'IBM API Connect Developer Portal/' . $apicVersion['value'] . ' (' . $apicVersion['description'] . ') ' . $hostname);

    // Enable auto-accept of self-signed certificates if this
    // has been set in the module config by an admin.
    self::curl_set_accept_ssl($ch);

    if (isset($mutualAuth) && !empty($mutualAuth)) {
      if (isset($mutualAuth['certFile'])) {
        $tempCertFile = tmpfile();
        fwrite($tempCertFile, $mutualAuth['certFile']);
        $tempCertPath = stream_get_meta_data($tempCertFile);
        $tempCertPath = $tempCertPath['uri'];
        curl_setopt($ch, CURLOPT_SSLCERT, $tempCertPath);
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
      }
      if (isset($mutualAuth['keyFile'])) {
        $tempKeyFile = tmpfile();
        fwrite($tempKeyFile, $mutualAuth['keyFile']);
        $tempKeyPath = stream_get_meta_data($tempKeyFile);
        $tempKeyPath = $tempKeyPath['uri'];
        curl_setopt($ch, CURLOPT_SSLKEY, $tempKeyPath);
      }
    }

    $response = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = mb_substr($response, 0, $header_size);
    $contents = mb_substr($response, $header_size);
    $status = curl_getinfo($ch);
    curl_close($ch);
    $stringdata = [
      'header' => $header,
      'header_size' => $header_size, 
      'content' => $contents, 
      'status' => $status
    ];
    // preserve http response code from API call
    if (isset($status['http_code']) && !empty($status['http_code']) && is_int($status['http_code'])) {
      http_response_code($status['http_code']);
    }
    if (!isset($status['http_code'])) {
      $status['http_code'] = 200;
    }
    $response_headers = [];
    // Split header text into an array.
    $header_text = preg_split('/[\r\n]+/', $header);

    // Propagate headers to response.
    foreach ($header_text as $header) {
      if (preg_match('/^(?:kbn-version|Location|Content-Type|Content-Language|Set-Cookie|X-APIM):/i', $header)) {
        $response_headers[] = $header;
      }
    }
    foreach ($header_text as $header) {
      if (preg_match('/^(?:Content-Disposition):/i', $header)) {
        $response_headers[] = $header;
      }
    }
    $header_array = [];
    foreach ($response_headers as $response_header) {
      $parts = explode(':', $response_header);
      $header_array[$parts[0]] = $parts[1];
    }

    $returnValue = ['headers' => $header_array, 'content' => $stringdata, 'statusCode' => $status['http_code']];

    return $returnValue;
  }


  public static function calldirectanalytics($analyticsClientUrl)
  {
    $returnValue = NULL;

    $utils = \Drupal::service('ibm_apim.utils');
    $session_store = \Drupal::service('tempstore.private')->get('ibm_apim');
    $config = \Drupal::service('ibm_apim.site_config');

    $url = $analyticsClientUrl . '/analytics/elasticsearch/_msearch';

    

    $params = '?analytics-services=analytics&az=44f4d04c-59da-4860-bcd0-5c5ad16c7f81&catalog_id=64836507-b228-4124-9780-461ed153327c&discover=true&manage=true&org_id=41664a79-0cc2-4e74-86cd-734fae303902';
    $ch = curl_init($url . $params);
    $headers = [];
    // $headers[] = 'X-IBM-Consumer-Context: ' . $config->getOrgId() . '.' . $config->getEnvId();
    // $lang_name = \Drupal::languageManager()->getCurrentLanguage()->getId();
    // $lang_name = $utils->convert_lang_name($lang_name);
    // if (isset($lang_name)) {
    //   $headers[] = 'Accept-Language: ' . $lang_name;
    // }

    $bearer_token = $session_store->get('auth');
    if (isset($bearer_token)) {
      $headers[] = 'Authorization: Bearer ' . $bearer_token;
    }
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    $headers[] = 'Content-Type: application/json';
    // if (isset($extraHeaders)) {
    //   foreach ($extraHeaders as $key => $value) {
    //     $headers[] = $value;
    //   }
    // }
    // if (\Drupal::hasContainer()) {
    //   $use_proxy = (bool) \Drupal::config('ibm_apim.settings')->get('use_proxy');
    //   if ($use_proxy === TRUE) {
    //     $proxy_url = \Drupal::config('ibm_apim.settings')->get('proxy_url');
    //     if ($proxy_url !== NULL && !empty($proxy_url)) {
    //       curl_setopt($ch, CURLOPT_PROXY, $proxy_url);
    //       $proxy_type = \Drupal::config('ibm_apim.settings')->get('proxy_type');
    //       if ($proxy_type !== NULL) {
    //         curl_setopt($ch, CURLOPT_PROXYTYPE, $proxy_type);
    //       }
    //       $proxy_auth = \Drupal::config('ibm_apim.settings')->get('proxy_auth');
    //       if ($proxy_auth !== NULL && !empty($proxy_auth)) {
    //         curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_auth);
    //       }
    //     }
    //   }
    // }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    //curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    // set a custom UA string for the portal
    // $apicVersion = \Drupal::state()->get('ibm_apim.version');
    // $hostname = gethostname();
    // if (!isset($hostname)) {
    //   $hostname = '';
    // }
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.100 Safari/537.36');

    // Enable auto-accept of self-signed certificates if this
    // has been set in the module config by an admin.
    self::curl_set_accept_ssl($ch);

    // if (isset($mutualAuth) && !empty($mutualAuth)) {
    //   if (isset($mutualAuth['certFile'])) {
    //     $tempCertFile = tmpfile();
    //     fwrite($tempCertFile, $mutualAuth['certFile']);
    //     $tempCertPath = stream_get_meta_data($tempCertFile);
    //     $tempCertPath = $tempCertPath['uri'];
    //     curl_setopt($ch, CURLOPT_SSLCERT, $tempCertPath);
    //     curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
    //   }
    //   if (isset($mutualAuth['keyFile'])) {
    //     $tempKeyFile = tmpfile();
    //     fwrite($tempKeyFile, $mutualAuth['keyFile']);
    //     $tempKeyPath = stream_get_meta_data($tempKeyFile);
    //     $tempKeyPath = $tempKeyPath['uri'];
    //     curl_setopt($ch, CURLOPT_SSLKEY, $tempKeyPath);
    //   }
    // }

    $response = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = mb_substr($response, 0, $header_size);
    $contents = mb_substr($response, $header_size);
    $status = curl_getinfo($ch);
    curl_close($ch);
    $stringdata = [
      'header' => $header,
      'header_size' => $header_size, 
      'content' => $contents, 
      'status' => $status
    ];
    // preserve http response code from API call
    if (isset($status['http_code']) && !empty($status['http_code']) && is_int($status['http_code'])) {
      http_response_code($status['http_code']);
    }
    if (!isset($status['http_code'])) {
      $status['http_code'] = 200;
    }
    $response_headers = [];
    // Split header text into an array.
    $header_text = preg_split('/[\r\n]+/', $header);

    // Propagate headers to response.
    foreach ($header_text as $header) {
      if (preg_match('/^(?:kbn-version|Location|Content-Type|Content-Language|Set-Cookie|X-APIM):/i', $header)) {
        $response_headers[] = $header;
      }
    }
    foreach ($header_text as $header) {
      if (preg_match('/^(?:Content-Disposition):/i', $header)) {
        $response_headers[] = $header;
      }
    }
    $header_array = [];
    foreach ($response_headers as $response_header) {
      $parts = explode(':', $response_header);
      $header_array[$parts[0]] = $parts[1];
    }

    $returnValue = ['headers' => $header_array, 'content' => $stringdata, 'statusCode' => $status['http_code']];

    return $returnValue;
  }

  /**
   * Generic API download proxy, used for documents and wsdls
   * if node is passed in then it will save the content as the swagger doc for that api
   *
   * @param $url
   * @param $verb
   * @param null $node
   * @param bool $filter
   * @param null $data
   * @param null $extraHeaders An array of headers to be added to the request of the form $array[] = "headerName: value";
   * @param null $mutualAuth An array with mutual authentication information (used in analytics)
   *
   * @return null|array
   * @throws \Exception
   */
  public static function proxy($url, $verb = 'GET', $node = NULL, $filter = FALSE, $data = NULL, $extraHeaders = NULL, $mutualAuth = NULL): ?array
  {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, [$url, $verb]);

    $utils = \Drupal::service('ibm_apim.utils');
    $session_store = \Drupal::service('tempstore.private')->get('ibm_apim');
    $config = \Drupal::service('ibm_apim.site_config');

    if (empty($url)) {
      drupal_set_message(t('URL not specified. Specify a valid URL and try again.'), 'error');
      return NULL;
    }
    if (mb_strpos($url, 'https://') !== 0) {
      $siteConfig = \Drupal::service('ibm_apim.site_config');

      $hostPieces = $siteConfig->parseApimHost();
      if (isset($hostPieces['url'])) {
        $url = $hostPieces['url'] . $url;
      } else {
        drupal_set_message(t('APIC Hostname not set. Aborting'), 'error');
        return NULL;
      }
    }

    // remove any double /consumer-api calls
    if (mb_strpos($url, '/consumer-api/consumer-api') !== 0) {
      $url = str_replace('/consumer-api/consumer-api', '/consumer-api', $url);
    }

    $ch = curl_init($url);

    $headers = [];
    $headers[] = 'X-IBM-Consumer-Context: ' . $config->getOrgId() . '.' . $config->getEnvId();
    $lang_name = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $lang_name = $utils->convert_lang_name($lang_name);
    if (isset($lang_name)) {
      $headers[] = 'Accept-Language: ' . $lang_name;
    }

    $bearer_token = $session_store->get('auth');
    if (isset($bearer_token)) {
      $headers[] = 'Authorization: Bearer ' . $bearer_token;
    }

    if (isset($node)) {
      $headers[] = 'Accept: application/vnd.ibm-apim.swagger2+yaml';
      $headers[] = 'Content-Type: application/vnd.ibm-apim.swagger2+yaml';
    }
    if ($verb !== 'GET') {
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $verb);
      $headers[] = 'Content-Type: application/json';
    }
    if ($verb === 'HEAD') {
      curl_setopt($ch, CURLOPT_NOBODY, TRUE);
      curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    }

    if (isset($extraHeaders)) {
      foreach ($extraHeaders as $key => $value) {
        $headers[] = $value;
      }
    }
    // proxy settings
    if (\Drupal::hasContainer()) {
      $use_proxy = (bool) \Drupal::config('ibm_apim.settings')->get('use_proxy');
      if ($use_proxy === TRUE) {
        $proxy_url = \Drupal::config('ibm_apim.settings')->get('proxy_url');
        if ($proxy_url !== NULL && !empty($proxy_url)) {
          curl_setopt($ch, CURLOPT_PROXY, $proxy_url);
          $proxy_type = \Drupal::config('ibm_apim.settings')->get('proxy_type');
          if ($proxy_type !== NULL) {
            curl_setopt($ch, CURLOPT_PROXYTYPE, $proxy_type);
          }
          $proxy_auth = \Drupal::config('ibm_apim.settings')->get('proxy_auth');
          if ($proxy_auth !== NULL && !empty($proxy_auth)) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_auth);
          }
          $apim_rest_trace = (bool) \Drupal::config('ibm_apim.settings')->get('apim_rest_trace');
          if ($apim_rest_trace === TRUE) {
            \Drupal::logger('ibm_apim_rest')->debug('Proxy URL: %data', ['%data' => $proxy_url]);
          }
        }
      }
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    if (\Drupal::hasContainer()) {
      $apim_rest_trace = (bool) \Drupal::config('ibm_apim.settings')->get('apim_rest_trace');
      if ($apim_rest_trace === TRUE) {
        curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
        \Drupal::logger('ibm_apim_rest')->debug('Payload: %data', ['%data' => serialize($data)]);
      }
    }

    if ($verb === 'PUT' || $verb === 'POST' || $verb === 'PATCH') {
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }

    // set a custom UA string for the portal
    $apicVersion = \Drupal::state()->get('ibm_apim.version');
    $hostname = gethostname();
    if (!isset($hostname)) {
      $hostname = '';
    }
    curl_setopt($ch, CURLOPT_USERAGENT, 'IBM API Connect Developer Portal/' . $apicVersion['value'] . ' (' . $apicVersion['description'] . ') ' . $hostname);

    // Enable auto-accept of self-signed certificates if this
    // has been set in the module config by an admin.
    self::curl_set_accept_ssl($ch);

    if (isset($mutualAuth) && !empty($mutualAuth)) {
      if (isset($mutualAuth['certFile'])) {
        $tempCertFile = tmpfile();
        fwrite($tempCertFile, $mutualAuth['certFile']);
        $tempCertPath = stream_get_meta_data($tempCertFile);
        $tempCertPath = $tempCertPath['uri'];
        curl_setopt($ch, CURLOPT_SSLCERT, $tempCertPath);
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
      }
      if (isset($mutualAuth['keyFile'])) {
        $tempKeyFile = tmpfile();
        fwrite($tempKeyFile, $mutualAuth['keyFile']);
        $tempKeyPath = stream_get_meta_data($tempKeyFile);
        $tempKeyPath = $tempKeyPath['uri'];
        curl_setopt($ch, CURLOPT_SSLKEY, $tempKeyPath);
      }
    }

    $response = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = mb_substr($response, 0, $header_size);
    $contents = mb_substr($response, $header_size);
    $status = curl_getinfo($ch);
    curl_close($ch);

    // preserve http response code from API call
    if (isset($status['http_code']) && !empty($status['http_code']) && is_int($status['http_code'])) {
      http_response_code($status['http_code']);
    }
    if (!isset($status['http_code'])) {
      $status['http_code'] = 200;
    }
    $response_headers = [];
    // Split header text into an array.
    $header_text = preg_split('/[\r\n]+/', $header);

    // Propagate headers to response.
    foreach ($header_text as $header) {
      if (preg_match('/^(?:kbn-version|Location|Content-Type|Content-Language|Set-Cookie|X-APIM):/i', $header)) {
        $response_headers[] = $header;
      }
    }
    // for YAML download force the filename, otherwise will default to version number
    if (isset($node)) {
      $response_headers[] = 'Content-Disposition: attachment; filename="apidownload.yaml"';
    } else {
      // use original filename if set
      foreach ($header_text as $header) {
        if (preg_match('/^(?:Content-Disposition):/i', $header)) {
          $response_headers[] = $header;
        }
      }
    }

    if (isset($node, $contents) && $node !== 'dummy') {
      $data = $contents;
      if (!isset($node->api_resources->value) || $node->api_resources->value !== $data) {
        $node->set('api_resources', $data);
        $node->save();
      }
    }
    $header_array = [];
    foreach ($response_headers as $response_header) {
      $parts = explode(':', $response_header);
      $header_array[$parts[0]] = $parts[1];
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    if ($filter === TRUE) {
      $returnValue = ['headers' => $header_array, 'content' => $contents, 'statusCode' => $status['http_code']];
    } else {
      print $contents;
      $returnValue = NULL;
    }
    return $returnValue;
  }

  /**
   * @return array|null
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  private static function getPlatformToken(): ?array
  {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $token = [];
    $site_config = \Drupal::service('ibm_apim.site_config');
    $url = '/token';
    $platformApiEndpoint = $site_config->getPlatformApimEndpoint();
    if (isset($platformApiEndpoint)) {
      $url = $platformApiEndpoint . $url;
    } else {
      drupal_set_message(t('APIC Hostname not set. Aborting'), 'error');
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      return NULL;
    }
    $clientId = $site_config->getClientId() ?? '';
    $clientSecret = $site_config->getClientSecret() ?? '';

    $requestBody = [
      'client_id' => $clientId,
      'client_secret' => $clientSecret,
      'grant_type' => 'client_credentials',
    ];
    $result = self::post($url, json_encode($requestBody), NULL);
    if (isset($result) && (int) $result->code >= 200 && (int) $result->code < 300) {
      $data = $result->data;
      if (isset($data['access_token'])) {
        $token['access_token'] = $data['access_token'];
      }
      if (isset($data['expires_in'])) {
        $token['expires_in'] = time() + (int) $data['expires_in'];
      }
      if (isset($data['token_type'])) {
        $token['token_type'] = $data['token_type'];
      }
      \Drupal::state()->set('ibm_apic_mail.token', $token);
    } elseif ($result !== null) {
      \Drupal::logger('ibm_apim')->info('call: get platform token exception %code %message', [
        '%message' => $result->data,
        '%code' => $result->code,
      ]);
    } else {
      \Drupal::logger('ibm_apim')->info('call: get platform token exception: result not set');
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $token;
  }
}
