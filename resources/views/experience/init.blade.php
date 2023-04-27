<!doctype html>
<html>
    <head>
        @if(!empty($shopUrls['shopUrls']->getFaviconUrl()))
            <link rel="icon" href="{{$shopUrls['shopUrls']->getFaviconUrl()}}"/>
        @endif

        <meta name="robots" content="noindex,nofollow"/>
        <meta name="viewport" content="width=device-width, initial-scale=1 maximum-scale=1"/>
        <title>{{ $shop->getShopName() }}</title>

        @foreach ($shopAssets['children']->get('header') as $script)
            @if ($script['asset_type'] == 'js')
                @if ($script['is_asynchronous'])
                    <script src="{{$script['asset_url']}}" async></script>
                @else
                    <script src="{{$script['asset_url']}}"></script>
                @endif
            @elseif($script['asset_type'] == 'css')
                <link rel="stylesheet" href="{{ $script['asset_url'] }}"/>
            @endif
        @endforeach

        <script type="text/javascript">
            window.initialTimestamps = [];
            var bugsnagApiKey = "{{ $keys['bugsnagApiKey'] }}";
            var enableConsole = {{ $indicators['enableConsole'] ? 'true' : 'false' }};
            var environment = {!! json_encode($indicators['environment']) !!};
            var shopIdentifier = "{{ $shop->getPlatformIdentifier() }}";
            var shopAlias = "{{ $shop->getPlatformDomain() }}";
            var shopName = "{{ $shop->getShopName() }}";
            var platformType = "{{ $shop->getPlatformType() }}";
            var publicOrderId = "{{ $publicOrderId ?? '' }}";
            var returnUrl = {!! json_encode($shopUrls['returnToCart']) !!};
            var loginUrl = {!! json_encode($shopUrls['returnToCheckoutUrl']) !!};
            var supportEmail = "{{ $shop->getSupportEmail() }}";
            var initializedOrder = {!! json_encode($initResponse) !!};
            var storeLoadTimesLocally = false;

            @if(!empty($shopUrls['shopUrls']->getLogoUrl()))
                var headerLogoUrl = "{{ $shopUrls['shopUrls']->getLogoUrl() }}";
            @endif
        </script>

        @if ($flags->contains('LOG'))
            <script type="text/javascript">
                var cartId = "{{ $cartID ?? '' }}";
                console.log({shopAlias, platformType, cartId, publicOrderId, environment, shopIdentifier, initializedOrder})
            </script>
        @endif

        @if ($flags->contains('LOADTIME'))
            <!-- @ include ( 'checkoutExperience.assets.loadTime') -->
            <script type="text/javascript">
                storeLoadTimesLocally = true;
            </script>
        @endif

        @if ($stylesheet)
            <link rel="stylesheet" href="{{ $stylesheet }}">
        @endif
    </head>

    <body>

        <div class="header"></div>
        <div id="main">
            <div class="container"></div>
        </div>

        @if ($flags->contains('LOG'))
            <div>
                <a href="{{ $shopUrls['shopUrls']->getBackToCartUrl() }}">Return to cart</a>
            </div>
            <div class="debug">
                <p>SHOP: <?php dump($shop->toArray()); ?></p>
                <p>SHOP_URLS: <?php dump($shopUrls); ?></p>
                <p>SCRIPTS: <?php dump($shopAssets->toArray()); ?></p>
                <p>FLAGS: <?php dump($flags->toArray()); ?></p>
            </div>

            <!--
            Next div will contain all load times foir display - if FF to log is turned ON
            -->
            <div id="stats" style="border: 1px solid #000; margin: 10px;">
                <p>{!! json_encode($indicators['loadTimes']) !!}</p>
                <!-- <p>Start loading template: {{ \Carbon\Carbon::createFromTimestamp(microtime(true))->format('H:i:s.u') }}</p> -->
            </div>
            <!--
            Previous div will contain all load times foir display - if FF to log is turned ON
            -->
        @endif

        @foreach ($shopAssets['children']->get('body') as $script)
            @if ($script['asset_type'] == 'js')
                @if ($script['is_asynchronous'])
                    <script src="{{$script['asset_url']}}" async></script>
                @else
                    <script src="{{$script['asset_url']}}"></script>
                @endif
            @elseif($script['asset_type'] == 'css')
                <link rel="stylesheet" href="{{ $script['asset_url'] }}"/>
            @endif
        @endforeach
        <script type="text/javascript" src="{{ $shopAssets['template']->getUrl() }}" id="checkout_template"></script>
    </body>

    <footer>
        @foreach ($shopAssets['children']->get('footer') as $script)
            @if ($script['asset_type'] == 'js')
                @if ($script['is_asynchronous'])
                    <script src="{{$script['asset_url']}}" async></script>
                @else
                    <script src="{{$script['asset_url']}}"></script>
                @endif
            @elseif($script['asset_type'] == 'css')
                <link rel="stylesheet" href="{{ $script['asset_url'] }}"/>
            @endif
        @endforeach
    </footer>
</html>
