<?php
/**
 * This file is Copyright AltoLabs 2016.
 *
 * @category Mage
 * @package  AltoLabs_Snappic
 * @author   AltoLabs <hi@altolabs.co>
 */

class AltoLabs_Snappic_OauthController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
      $this->loadLayout();

      $this->getLayout()->getBlock('head')->addItem('skin_js', 'js/snappic/jsoauth.js');

      $block = $this->getLayout()->createBlock('core/text');
      $block->setText($this->indexBodyBlock());
      $this->getLayout()->getBlock('content')->append($block);

      $this->renderLayout();
    }

    protected function indexBodyBlock() {
      $helper = Mage::helper('altolabs_snappic');
      $domain = $helper->getDomain();
      $adminHtml = $helper->getAdminHtmlPath();

      $components = parse_url(Mage::getBaseUrl());
      $baseUrl = $components['host'].$components['path'];

      $consumer = Mage::getModel('oauth/consumer')->load('Snappic', 'name');
      $consumerKey = $consumer->getKey();
      $consumerSecret = $this->getRequest()->getParam('secret');

      $payload = <<<HTML
<style>
#snappic_output, .snappic-msg {
  width: 100%;
  height: auto;
  font-size: 16px;
  font-weight: 400;
  letter-spacing: 1.2px;
  line-height: 1.2;
  border: 0;
  padding: 0;
}
.snappic-msg > div {
  width: 100%;
  text-align: center;
}
#snappic_output {
  text-align: center;
  margin: 0 0 42px 0;
}
#snappic_wrap {
  width: 100%;
  height: 100px;
  display: block;
  position: relative;
}
#snappic_wrap > div {
  position: absolute;
  top: 0; right: 0; left: 0;
  opacity: 0;
  transition: opacity 200ms linear;
}
.snappic-msg {
  top: 24px;
}
#snappic_wrap img {
  width: 100%;
  max-width: 460px;
  margin: 0 auto;
  transition: transform 200ms ease-in-out;
  cursor: pointer;
}
#snappic_wrap:not(.disabled) img:hover {
  transform: scale(1.02);
}
#snappic_wrap.disabled img {
  opacity: 0.15;
  cursor: auto;
  pointer-events: none;
}
</style>
<div id="snappic_output">So that we can make sure you're always displaying the right prices and inventory</div>
<div id="snappic_wrap">
  <div style="z-index:1"><img src="http://store.snappic.io/images/magento_authorize_snappic.png" onclick="Snappic.authorize()"></div>
  <div class="snappic-msg"><div>Retrieving authorization link...</div></div>
  <div class="snappic-msg"><div>Error validating authentication request. Please reload the page, or contact your administrator</div></div>
  <div class="snappic-msg"><div>Error retrieving authorization link. Please reload the page, or contact your administrator</div></div>
  <div class="snappic-msg"><div>Almost there! Please wait while we verify the authentication token</div></div>
  <div class="snappic-msg"><div>All done! Redirecting you to Snappic to continue your registration...</div></div>
  <div class="snappic-msg"><div>Error verifying authentication token. Please reload the page, or contact your administrator</div></div>
  <div class="snappic-msg"><div>Please log into your Magento administrator account</div></div>
</div>
<script>
var Snappic = {};
Snappic._msg = document.querySelector('#snappic_output');
Snappic._wrap = document.querySelector('#snappic_wrap');
Snappic.show = function (m) {
  var n = document.querySelectorAll('#snappic_wrap > div');
  for (var a = 0; a < n.length; a++) {
    n[a].style.opacity = '';
  }
  n[m].style.opacity = '1';
  if (m === 0) {
    document.querySelector('#snappic_wrap').classList.remove('disabled');
  } else {
    document.querySelector('#snappic_wrap').classList.add('disabled');
  }
};
</script>
HTML;

      if ($consumerSecret != $consumer->getSecret()) {
        $payload .= "<script>Snappic.show(2);</script>";
        return $payload;
      }

      $payload .= <<<JS
<script>
Snappic.setPin = function(pin) {
  this.show(4);
  this._oauth.setVerifier(pin);
  this._oauth.fetchAccessToken( function() {
    this.show(5);
    token = '$consumerKey:$consumerSecret:' + this._oauth.getAccessTokenKey() + ':' + this._oauth.getAccessTokenSecret();
    window.location = 'http://www.snappic.io?' + 'provider=magento&domain=' + encodeURIComponent('$domain') + '&access_token=' + encodeURIComponent(token);
  }.bind(this), function(data) {
    this.show(6);
    console.error(data);
  }.bind(this));
}.bind(Snappic);
Snappic.authorizeUrl = '';
Snappic.authorize = function () {
  this.show(7);
  window.open(this.authorizeUrl, '_blank', 'location=yes,clearcache=yes');
}.bind(Snappic);
Snappic.init = function () {
  this.show(1);
  var p = window.location.protocol;
  this._oauth = new OAuth({
    consumerKey: '$consumerKey',
    consumerSecret: '$consumerSecret',
    requestTokenUrl: p + '//$baseUrl/oauth/initiate',
    authorizationUrl: p + '//$baseUrl/$adminHtml/oauth_authorize',
    accessTokenUrl: p + '//$baseUrl/oauth/token',
    callbackUrl: p + '//$baseUrl/shopinsta/oauth/callback'
  });
  this._oauth.fetchRequestToken(function(url) {
    console.log(url);
    this.authorizeUrl = url;
    this.show(0);
  }.bind(this), function(data) {
    this.show(3);
    console.log(data);
  });
}.bind(Snappic);
Snappic.init();
</script>
JS;
      return $payload;
    }

    public function callbackAction()
    {
      $this->loadLayout();

      $block = $this->getLayout()->createBlock('core/text');
      $block->setText($this->callbackHtml());
      $this->getLayout()->getBlock('content')->append($block);

      $this->renderLayout();
    }

    protected function callbackHtml()
    {
        $payload = <<<CBHTML
<div style='width:100%;height:auto;font-size:16px;font-weight:400;letter-spacing:1.2px;line-height:1.2;text-align:center;margin:0 0 42px 0;border:0;padding:0'>Completing authentication...</div>
<script>
  (function () {
    var qs = document.location.search.split('+').join(' '), params = {}, tokens, re = /[?&]?([^=]+)=([^&]*)/g;
    while (tokens = re.exec(qs)) { params[decodeURIComponent(tokens[1])] = decodeURIComponent(tokens[2]); }
    window.opener.Snappic.setPin(params['oauth_verifier']);
    window.close();
  })();
</script>
CBHTML;
        return $payload;
    }
}
