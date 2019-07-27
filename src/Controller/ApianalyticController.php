<?php

namespace Drupal\apianalytic\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\apianalytic\RestAnalytic;
use Drupal\ibm_apim\Service\AnalyticsService;
use Drupal\ibm_apim\Service\SiteConfig;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\ibm_apim\Service\Utils;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class ApianalyticController extends ControllerBase
{

  protected $userUtils;
  protected $siteConfig;
  protected $utils;
  private $requestStack;
  //protected $analytics_service;

  public function __construct(UserUtils $userUtils, SiteConfig $config, AnalyticsService $analytics_service, RequestStack $request_stack, Utils $utils)
  {
    $this->userUtils = $userUtils;
    $this->siteConfig = $config;
    $this->analyticsService = $analytics_service;
    $this->requestStack = $request_stack;
    $this->utils = $utils;
  }


  public static function create(ContainerInterface $container)
  {
    return new static($container->get('ibm_apim.user_utils'), $container->get('ibm_apim.site_config'), $container->get('ibm_apim.analytics'), $container->get('request_stack'), $container->get('ibm_apim.utils'));
  }

  /**
   * Callback for the API.
   */


  public function renderResult(Request $request)
  {
    return new JsonResponse($this->CalProxy($request));
  }

  public function TestResult(Request $request)
  {
    echo $request;
    $response = new Response(t('Check ok call post'), 200);
    return $response;
  }

  public function CalProxy(Request $request)
  {
    $data = NULL;
    $consumer_org = $this->userUtils->getCurrentConsumerorg();
    if (isset($consumer_org) && isset($consumer_org['url'])) {
      $portal_analytics_service = $this->analyticsService->getDefaultService();
      if (isset($portal_analytics_service)) {
        $analyticsClientUrl = $portal_analytics_service->getClientEndpoint();
        if (isset($analyticsClientUrl)) {
          $query = \Drupal::entityQuery('node');
          $query->condition('type', 'consumerorg');
          $query->condition('consumerorg_url.value', $consumer_org['url']);
          $consumerOrgResults = $query->execute();
          if (isset($consumerOrgResults) && !empty($consumerOrgResults)) {
            $first = array_shift($consumerOrgResults);
            $consumerorg = Node::load($first);
            $consumerorgId = $consumerorg->consumerorg_id->value;

            $pOrgId = $this->siteConfig->getOrgId();
            $catalogId = $this->siteConfig->getEnvId();
            // get the incoming POST payload
            // $appID = '';
            // $fromDate = '';
            // $toDate = '';
            // $data = $request->getContent();
            // $Params = explode("&", $data);
            // foreach ($Params AS $item) {
            //   $posappId = explode("appId=", $item);
            //   $posfromDate = explode("fromDate=", $item);
            //   $postoDate = explode("toDate=", $item);
            //   if (count($posappId) > 1) {
            //       $appID = str_replace("appId=", "", $item);
            //   }
            //   if (count($posfromDate) > 1) {
            //     $fromDate = str_replace("fromDate=", "", $item);
            //   }
            //   if (count($postoDate) > 1) {
            //     $toDate = str_replace("toDate=", "", $item);
            //   }
            // }
            $appID = $request->get('appId');
            $fromDate = $request->get('fromDate');
            $toDate = $request->get('toDate');
            $size = $request->get('size');
            $url = $analyticsClientUrl . '/api/apiconnect/anv';

            $verb = 'POST';
            $url = $url . '?org_id=' . $pOrgId . '&catalog_id=' . $catalogId . '&developer_org_id=' . $consumerorgId . '&manage=true&dashboard=true';

            $headers = [];

            // Need to use Mutual TLS on the Analytics Client Endpoint
            $mutualAuth = [];
            $analytics_tls_client = $portal_analytics_service->getClientEndpointTlsClientProfileUrl();
            if (isset($analytics_tls_client)) {
              $client_endpoint_tls_client_profile_url = $analytics_tls_client;
              $tls_profiles = \Drupal::service('ibm_apim.tls_client_profiles')->getAll();
              if (isset($tls_profiles) && !empty($tls_profiles)) {
                foreach ($tls_profiles as $tls_profile) {
                  if ($tls_profile->getUrl() == $client_endpoint_tls_client_profile_url) {
                    $keyfile = $tls_profile->getKeyFile();
                    if (isset($keyfile)) {
                      $mutualAuth['keyFile'] = $keyfile;
                    }
                    $certfile = $tls_profile->getCertFile();
                    if ($certfile) {
                      $mutualAuth['certFile'] = $certfile;
                    }
                  }
                }
              }
            }
            if (empty($mutualAuth)) {
              $mutualAuth = NULL;
            }
            $headers[] = 'kbn-xsrf: 5.5.1';
            
            $dataApi = '{
              "meta": 
              {
                  "kbn-version": "5.5.1",
                  "anv-version": "1.1.3"
              },
              "requests": 
              [
                {
                  "id": "api-calls",
                  "time":
                  {
                      "field": "datetime",
                      "type": "single",
                      "gte": ' . $fromDate . ',
                      "lte": ' . $toDate . '
                  },
                  "filters":
                  [
                    {
                      "field": "app_id",
                      "value": "' . $appID . '"
                    }
                  ],
                  "metrics":
                    [
                      {
                        "type": "raw",
                        "size": ' . $size . ',
                        "th": false
                      }
                    ]
                }
              ]
            }';
            $response_object = RestAnalytic::proxy($url, $verb, NULL, TRUE, $dataApi, $headers, $mutualAuth);
          } else {
          }
        } else {
        }
      } else {
      }
    } else {
    }
    return array(
      "data" => $response_object,
    );
  }
}
