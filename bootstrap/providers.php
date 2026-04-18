<?php

use App\Providers\AppServiceProvider;
use App\Providers\TelescopeServiceProvider;
use Webkul\Admin\Providers\AdminServiceProvider;
use Webkul\Attribute\Providers\AttributeServiceProvider;
use Webkul\BookingProduct\Providers\BookingProductServiceProvider;
use Webkul\CartRule\Providers\CartRuleServiceProvider;
use Webkul\CatalogRule\Providers\CatalogRuleServiceProvider;
use Webkul\Category\Providers\CategoryServiceProvider;
use Webkul\Checkout\Providers\CheckoutServiceProvider;
use Webkul\CMS\Providers\CMSServiceProvider;
use Webkul\Core\Providers\CoreServiceProvider;
use Webkul\Core\Providers\EnvValidatorServiceProvider;
use Webkul\Customer\Providers\CustomerServiceProvider;
use Webkul\DataGrid\Providers\DataGridServiceProvider;
use Webkul\DataTransfer\Providers\DataTransferServiceProvider;
use Webkul\DebugBar\Providers\DebugBarServiceProvider;
use Webkul\DeliveryZones\Providers\DeliveryZonesServiceProvider;
use Webkul\ExternalPayments\Providers\ExternalPaymentsServiceProvider;
use Webkul\FPC\Providers\FPCServiceProvider;
use Webkul\GDPR\Providers\GDPRServiceProvider;
use Webkul\ImportExport\Providers\ImportExportServiceProvider;
use Webkul\Installer\Providers\InstallerServiceProvider;
use Webkul\Inventory\Providers\InventoryServiceProvider;
use Webkul\MagicAI\Providers\MagicAIServiceProvider;
use Webkul\ManagerApp\Providers\ManagerAppServiceProvider;
use Webkul\Marketing\Providers\MarketingServiceProvider;
use Webkul\Markup\Providers\MarkupServiceProvider;
use Webkul\Menu\Providers\MenuServiceProvider;
use Webkul\Notification\Providers\NotificationServiceProvider;
use Webkul\Payment\Providers\PaymentServiceProvider;
use Webkul\PaymentConfirmation\Providers\PaymentConfirmationServiceProvider;
use Webkul\Paypal\Providers\PaypalServiceProvider;
use Webkul\Product\Providers\ProductServiceProvider;
use Webkul\ProductTag\Providers\ProductTagServiceProvider;
use Webkul\PushNotification\Providers\PushNotificationServiceProvider;
use Webkul\Rule\Providers\RuleServiceProvider;
use Webkul\Sales\Providers\SalesServiceProvider;
use Webkul\Shipping\Providers\ShippingServiceProvider;
use Webkul\Shop\Providers\ShopServiceProvider;
use Webkul\Sitemap\Providers\SitemapServiceProvider;
use Webkul\SocialLogin\Providers\SocialLoginServiceProvider;
use Webkul\SocialShare\Providers\SocialShareServiceProvider;
use Webkul\Supplier\Providers\SupplierServiceProvider;
use Webkul\Tax\Providers\TaxServiceProvider;
use Webkul\Theme\Providers\ThemeServiceProvider;
use Webkul\User\Providers\UserServiceProvider;

return [
    AppServiceProvider::class,
    TelescopeServiceProvider::class,
    AdminServiceProvider::class,
    AttributeServiceProvider::class,
    BookingProductServiceProvider::class,
    CMSServiceProvider::class,
    CartRuleServiceProvider::class,
    CatalogRuleServiceProvider::class,
    CategoryServiceProvider::class,
    CheckoutServiceProvider::class,
    CoreServiceProvider::class,
    EnvValidatorServiceProvider::class,
    CustomerServiceProvider::class,
    DataGridServiceProvider::class,
    DataTransferServiceProvider::class,
    DebugBarServiceProvider::class,
    DeliveryZonesServiceProvider::class,
    FPCServiceProvider::class,
    GDPRServiceProvider::class,
    ImportExportServiceProvider::class,
    InstallerServiceProvider::class,
    InventoryServiceProvider::class,
    MagicAIServiceProvider::class,
    ManagerAppServiceProvider::class,
    MarketingServiceProvider::class,
    MarkupServiceProvider::class,
    MenuServiceProvider::class,
    NotificationServiceProvider::class,
    PaymentConfirmationServiceProvider::class,
    ExternalPaymentsServiceProvider::class,
    PaymentServiceProvider::class,
    PaypalServiceProvider::class,
    ProductTagServiceProvider::class,
    ProductServiceProvider::class,
    PushNotificationServiceProvider::class,
    RuleServiceProvider::class,
    SalesServiceProvider::class,
    ShippingServiceProvider::class,
    ShopServiceProvider::class,
    SitemapServiceProvider::class,
    SocialLoginServiceProvider::class,
    SocialShareServiceProvider::class,
    SupplierServiceProvider::class,
    TaxServiceProvider::class,
    ThemeServiceProvider::class,
    UserServiceProvider::class,
];
