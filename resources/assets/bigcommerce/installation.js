(() => {
    const SHOULD_OVERRIDE = true
    const CUSTOM_CHECKOUT_INIT_URL = '<YOUR_PUBLIC_SERVER_URL>experience/init'

    if (!SHOULD_OVERRIDE) {
        return
    }

    const boldCheckoutLoaded = new Promise(res => {
        console.log('Waiting for Bold Checkout to load...')
        const interval = setInterval(() => {
            if (window?.BOLD?.checkout?.fetchBigcommerceCart) {
                clearInterval(interval)
                res(true)
            }
        }, 1)
    }).then(() => console.log('Bold checkout loaded. Overriding.'))

    const bigCommerceCart = boldCheckoutLoaded
        .then(() => new Promise((res, rej) => window.BOLD.checkout.fetchBigcommerceCart((cart) => {
            if (cart) {
                res(cart)
            } else {
                rej('No BigCommerce cart found')
            }
        })))

    const customerJwt = boldCheckoutLoaded
        .then(() => new Promise((res) => {
            window.BOLD.checkout.getCustomer(res)
        }))

    const selectCheckoutButtons = () => document.querySelectorAll('a[href="/checkout"]')
    const disableCheckoutButtons = () => selectCheckoutButtons().forEach(btn => btn.setAttribute('disabled', true))
    const enableCheckoutButtons = () => selectCheckoutButtons().forEach(btn => btn.removeAttribute('disabled'))
    const makeCheckoutUrl = async () => {
        const bigCCartData = await bigCommerceCart
        const bigCToken = await customerJwt
        const url = new URL(`${CUSTOM_CHECKOUT_INIT_URL}/${window.BOLD.checkout.secureURL}`)
        url.searchParams.set('return_url', window.location.href)
        url.searchParams.set('checkout_local_time', window.BOLD.checkout.localTime())
        if (bigCCartData.id) {
            url.searchParams.set('cart_id', bigCCartData.id)
        }
        if (bigCCartData.customerId) {
            url.searchParams.set('customer_id', bigCCartData.customerId)
        }
        if (bigCToken) {
            url.searchParams.set('user_access_token', bigCToken)
        }
        return url.toString()
    }

    const overrideWithCustomCheckoutUrl = async () => {
        return makeCheckoutUrl()
            .then(checkoutUrl => selectCheckoutButtons().forEach(btn => btn.setAttribute('href', checkoutUrl)))
    }

    const overrideNewCheckoutButtonsWhenAddedToDOM = () => {
        new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                if (mutation.type === 'childList') {
                    overrideWithCustomCheckoutUrl()
                    return
                }
            }
        }).observe(document.body, {childList: true, subtree: true})
    }

    disableCheckoutButtons()
    boldCheckoutLoaded
        .then(() => window.BOLD.checkout.disable())
        .then(overrideWithCustomCheckoutUrl)
        .then(overrideNewCheckoutButtonsWhenAddedToDOM)
        .then(enableCheckoutButtons())
        .catch((e) => {
            console.error(e)
            enableCheckoutButtons()
        })
})()
