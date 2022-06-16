<?php

namespace Drupal\gv_fanatics_plus_checkout\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Drupal\gv_fanatics_plus_checkout\CheckoutOrderSteps;

use Drupal\gv_fanatics_plus_utils\Crypto;

/**
 * Controlador de la ruta de listado de integrantes del usuario
 */
class IntegrantListController extends ControllerBase {

	private $apiClient;
	private $integrant;
	private $session;
	private $checkoutOrderManager;
	private $emailValidator;

	public static function create(ContainerInterface $container) {
		$session = $container->get('gv_fplus.session');
		$apiClient = $container->get('gv_fplus_dbm_api.client');
		$integrant = $container->get('gv_fanatics_plus_checkout.integrant');
		$checkoutOrderManager = $container->get('gv_fanatics_plus_checkout.checkout_order_manager');
		$emailValidator = $container->get('email.validator');
		
		return new static($apiClient, $session, $integrant, $checkoutOrderManager, $emailValidator);
	}
	
	public function __construct($apiClient, $session, $integrant, $checkoutOrderManager, $emailValidator) {
		$this->apiClient = $apiClient;
		$this->session = $session;
		$this->integrant = $integrant;
		$this->checkoutOrderManager = $checkoutOrderManager;
		$this->emailValidator = $emailValidator;
	}

	public function integrantList() {
		$integrants = $this->integrant->listMember($this->session->getIDClient())->List;
		foreach ($integrants as $index => $integrant) {
			$valid_email = $this->emailValidator->isValid($integrant->Email);
			if (!$valid_email) {
				$integrants[$index]->Email = $this->t('We do not have your email', [], []);
			}

			$switchIntegrantBuyUrl = Url::fromRoute('gv_fanatics_plus_checkout.form', ['step' => CheckoutOrderSteps::PRODUCT_SELECTION], ['query' => ['switch-integrant' => '1', 'integrant-client-id' => $this->checkoutOrderManager->encrypt($integrant->IntegrantID)]]);
			$switchIntegrantEditDataUrl = Url::fromRoute('gv_fplus_auth.user_profile_personal_data_form', [], ['query' => ['switch-integrant' => '1', 'integrant-client-id' => $this->checkoutOrderManager->encrypt($integrant->IntegrantID)]]);
			$seeSkiSlopesUrl = Url::fromRoute('gv_fanatics_plus_ski_slopes.history_integrant', [], ['query' => ['integrant' => Crypto::encrypt($integrant->IntegrantID)]]);
			$integrants[$index]->SwitchBuyUrl = $switchIntegrantBuyUrl;
			$integrants[$index]->SwitchEditProfileUrl = $switchIntegrantEditDataUrl;
			$integrants[$index]->SeeSkiSlopesUrl = $seeSkiSlopesUrl;
		}
		
		$addNewIntegrantUrl = Url::fromRoute('gv_fplus_auth.user_profile_personal_data_form', [], ['query' => ['register-new-integrant' => '1']]);
		
		return ['#attached' => ['library' => ['gv_fanatics_plus_checkout/integrant_list'], ], '#theme' => 'gv_fanatics_plus_checkout_integrant_list', '#integrants' => $integrants, '#add_new_integrant_url' => $addNewIntegrantUrl];
	}

}

?>
