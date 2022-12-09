# The Platform.sh API


Documented online at https://api.platform.sh/docs/

Using Postman to Work with the API

[process described in the internal notes](https://async.plat.farm/t/explore-and-test-the-platform-sh-api-with-postman/1997)
mostly worked to get set up, but on my (popOS Chrome) combo it would not automatically perform the remote oauth from browser.
This is needed to begin any postman API session.

Pressing "fetch token" is supposed to launch a browser interaction that authenticates and returns a token to the Postman App.
Launch and auth worked, but it did not successfully send the responce back to the desktop app.

Instead, I have to do it slightly manually.
* In Postman Desktop,
  * Within the 'Platform.sh REST API' environment 'Authorization' tab
  * Which should have been set up with the `access_token` variable.
  * Click `Get new Token`
  * which launches a browser session
* "Authorize Postman to use your account" -> "Yes, authorize"
  * For me, that opens a modal "Open xdg-open" - which fails to find the `Postman` app for internal OS reasons.
* INSTEAD, copy the application URL that it's trying to launch:
  * eg `postman://app/oauth2/callback?code=jmU_...974&scope=&state=arandomstring`
* Launch that from the cli, with `xdg-open`
  * `xdg:open "postman://app/oauth2/callback?code=..." `
* This should switch back to the `postman` app with a prefilled token. "Use Token" for this session.
