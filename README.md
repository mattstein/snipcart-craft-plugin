# Snipcart Craft Plugin

Browse [Snipcart](https://snipcart.com/) orders, customers, and discounts from the Craft control panel. **This is a work in progress**, with discounts being completely un-tested and a web hook for (crudely) emailing order notifications. It should be useful out of the box, but any improvements are welcome!

![The Craft plugin's main view.](http://files.workingconcept.com/raw/snipcart-orders-8FPa5gvpLK.png)

## Installation and Setup

Drop the `snipcart` folder in your `craft/plugins` directory, then visit Settings → Plugins and install the Snipcart plugin.

Create yourself a private Snipcart API key by logging into the Snipcart control panel and creating a new Secret API Key from Account → Credentials. Use this new secret key for your Craft install. Once added, you can allow users access to the Snipcart section and all orders, customers, and coupons will be available for browsing.

## Limitations

- Information is read-only, and limited to what's available from the [Snipcart API](http://docs.snipcart.com/api-reference/introduction).
- There's not yet any caching in place, so browsing can be slow particularly on customer detail pages.
- The (relatively secret) web hook is available at `http://yoursite.com/actions/snipcart/webhooks/handle`, but `Snipcart_WebhooksController` is just a rough experiment at this point.
- I've not yet tested the Discounts view.
- I'm certain that the templates could be improved, so please don't judge too harshly.

Hopefully this will be helpful to someone integrating with Snipart. I'm open to whatever issues or pull requests anybody might have!
