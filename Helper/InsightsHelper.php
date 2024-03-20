<?php

namespace Algolia\AlgoliaSearch\Helper;

use Algolia\AlgoliaSearch\Api\InsightsClient;
use Algolia\AlgoliaSearch\Exceptions\AlgoliaException;
use Algolia\AlgoliaSearch\Helper\Configuration\PersonalizationHelper;
use Algolia\AlgoliaSearch\Api\Insights\EventsInterface;
use Algolia\AlgoliaSearch\Api\Insights\EventsInterfaceFactory;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;

class InsightsHelper
{
    /** @var string  */
    public const ALGOLIA_ANON_USER_TOKEN_COOKIE_NAME = '_ALGOLIA';

    /** @var string  */
    public const ALGOLIA_CUSTOMER_USER_TOKEN_COOKIE_NAME = 'aa-search';

    /** @var string */
    public const QUOTE_ITEM_QUERY_PARAM = 'algoliasearch_query_param';

    /** @var InsightsClient|null */
    protected ?InsightsClient $insightsClient = null;

    /** @var EventsInterface|null  */
    protected ?EventsInterface $eventsModel = null;

    /**
     * InsightsHelper constructor.
     *
     * @param ConfigHelper $configHelper
     * @param PersonalizationHelper $personalizationHelper
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param CustomerSession $customerSession
     * @param EventsInterfaceFactory $eventsFactory
     */
    public function __construct(
        private readonly ConfigHelper           $configHelper,
        private readonly PersonalizationHelper  $personalizationHelper,
        private readonly CookieManagerInterface $cookieManager,
        private readonly CookieMetadataFactory  $cookieMetadataFactory,
        private readonly CustomerSession        $customerSession,
        private readonly EventsInterfaceFactory $eventsFactory
    ) { }

    public function getPersonalizationHelper(): PersonalizationHelper
    {
        return $this->personalizationHelper;
    }

    public function getConfigHelper(): ConfigHelper
    {
        return $this->configHelper;
    }

    /**
     * @internal Intended for internal use only - visibility may change at a future time
     * @return InsightsClient
     */
    public function getInsightsClient(): InsightsClient
    {
        if (!$this->insightsClient) {
            $this->insightsClient = InsightsClient::create(
                $this->configHelper->getApplicationID(),
                $this->configHelper->getAPIKey()
            );
        }

        return $this->insightsClient;
    }

    /**
     * @return EventsInterface
     */
    public function getEventsModel(): EventsInterface
    {
        if (!$this->eventsModel) {
            $this->eventsModel = $this->eventsFactory->create([
                'insightsClient'         => $this->getInsightsClient(),
                'userToken'              => $this->getAnonymousUserToken(),
                'authenticatedUserToken' => $this->getAuthenticatedUserToken()
            ]);
        }
        return $this->eventsModel;
    }

    public function getAnonymousUserToken(): string
    {
        return $this->cookieManager->getCookie(self::ALGOLIA_ANON_USER_TOKEN_COOKIE_NAME);
    }

    public function getAuthenticatedUserToken(): string
    {
        $userToken = $this->cookieManager->getCookie(self::ALGOLIA_CUSTOMER_USER_TOKEN_COOKIE_NAME);
        if (!$userToken) {
            if ($this->customerSession->isLoggedIn()) {
                // set logged in user
                $userToken = $this->setAuthenticatedUserToken($this->customerSession->getCustomer());
            }
        }
        return $userToken ?? "";
    }

    protected function setAuthenticatedUserToken(Customer $customer): string|null
    {
        $userToken = base64_encode('customer-' . $customer->getId());
        $userToken = 'aa-' . preg_replace('/[^A-Za-z0-9\-]/', '', $userToken);
        $userToken = mb_substr($userToken, 0, 64); // character limit

        try {
            $metaData = $this->cookieMetadataFactory->createPublicCookieMetadata()
                ->setDurationOneYear()
                ->setPath('/')
                ->setHttpOnly(false)
                ->setSecure(false);
            $this->cookieManager->setPublicCookie(self::ALGOLIA_CUSTOMER_USER_TOKEN_COOKIE_NAME, $userToken, $metaData);
        } catch (\Exception $e) {
            $userToken = "";
        }

        return $userToken;
    }

    /**
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isOrderPlacedTracked(int $storeId = null): bool
    {
        return ($this->personalizationHelper->isPersoEnabled($storeId)
                && $this->personalizationHelper->isOrderPlacedTracked($storeId))
            || ($this->configHelper->isClickConversionAnalyticsEnabled($storeId)
                && $this->configHelper->getConversionAnalyticsMode($storeId) === 'place_order');
    }

    /**
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isAddedToCartTracked(int $storeId = null): bool
    {
        return ($this->personalizationHelper->isPersoEnabled($storeId)
                && $this->personalizationHelper->isCartAddTracked($storeId))
            || ($this->configHelper->isClickConversionAnalyticsEnabled($storeId)
                && $this->configHelper->getConversionAnalyticsMode($storeId) === 'add_to_cart');
    }

    /**
     * @param Customer $customer
     * @throws AlgoliaException
     * @internal This method is no longer compatible with PHP connector v4. Do not use.
     */
    public function setUserToken(Customer $customer)
    {
        throw new AlgoliaException("This method is no longer compatible with PHP connector v4.");
    }

    /**
     * @return bool
     */
    public function getUserAllowedSavedCookie(): bool
    {
        return !$this->configHelper->isCookieRestrictionModeEnabled()
            || !!$this->cookieManager->getCookie($this->configHelper->getDefaultConsentCookieName());
    }
}
