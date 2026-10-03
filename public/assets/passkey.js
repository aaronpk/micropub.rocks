/*
 * Passkey sign-up, sign-in, and adding a passkey to an account.
 *
 * Everything the script needs comes from data attributes and form fields on
 * the elements it binds to.
 */
(function () {
  'use strict';

  /* WebAuthn deals in ArrayBuffers; the wire format is base64url. */
  function fromBase64Url(value) {
    var padded = value.replace(/-/g, '+').replace(/_/g, '/');
    var binary = atob(padded + '='.repeat((4 - (padded.length % 4)) % 4));
    var bytes = new Uint8Array(binary.length);
    for (var i = 0; i < binary.length; i++) {
      bytes[i] = binary.charCodeAt(i);
    }
    return bytes;
  }

  function toBase64Url(buffer) {
    var bytes = new Uint8Array(buffer);
    var binary = '';
    for (var i = 0; i < bytes.length; i++) {
      binary += String.fromCharCode(bytes[i]);
    }
    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
  }

  async function post(url, body, csrf) {
    var headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    };
    if (csrf) {
      headers['X-CSRF-Token'] = csrf;
    }

    var response = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: headers,
      body: JSON.stringify(body || {})
    });

    var payload;
    try {
      payload = await response.json();
    } catch (e) {
      throw new Error('The server returned an unreadable response.');
    }

    if (!response.ok) {
      throw new Error(payload.error || 'Something went wrong.');
    }

    return payload;
  }

  function show(element, message) {
    if (!element) {
      return;
    }
    element.textContent = message;
    element.classList.toggle('hidden', message === '');
  }

  /*
   * A cancelled prompt and a genuine failure both arrive as exceptions.
   * "You cancelled" isn't an error worth showing.
   */
  function describe(error) {
    if (error && (error.name === 'NotAllowedError' || error.name === 'AbortError')) {
      return null;
    }
    if (error && error.name === 'InvalidStateError') {
      return 'This device already has a passkey for this account.';
    }
    return (error && error.message) || 'Something went wrong.';
  }

  function supported() {
    return typeof window.PublicKeyCredential !== 'undefined'
      && typeof navigator.credentials !== 'undefined';
  }

  async function createCredential(options) {
    var publicKey = options.publicKey;
    publicKey.challenge = fromBase64Url(publicKey.challenge);
    publicKey.user.id = fromBase64Url(publicKey.user.id);
    if (publicKey.excludeCredentials) {
      publicKey.excludeCredentials = publicKey.excludeCredentials.map(function (credential) {
        return Object.assign({}, credential, { id: fromBase64Url(credential.id) });
      });
    }

    var credential = await navigator.credentials.create({ publicKey: publicKey });

    return {
      clientDataJSON: toBase64Url(credential.response.clientDataJSON),
      attestationObject: toBase64Url(credential.response.attestationObject)
    };
  }

  /* ------------------------------------------------------------ sign up */

  async function register(button) {
    var email = (document.getElementById('passkey-email') || {}).value || '';
    var options = await post('/auth/register/challenge', { email: email });
    var credential = await createCredential(options);
    var result = await post('/auth/register', credential);
    window.location = result.redirect || '/';
  }

  /* ------------------------------------------------------------ sign in */

  async function signIn(button) {
    var options = await post('/auth/login/challenge');
    var publicKey = options.publicKey;
    publicKey.challenge = fromBase64Url(publicKey.challenge);
    if (publicKey.allowCredentials) {
      publicKey.allowCredentials = publicKey.allowCredentials.map(function (credential) {
        return Object.assign({}, credential, { id: fromBase64Url(credential.id) });
      });
    }

    var assertion = await navigator.credentials.get({ publicKey: publicKey });

    var result = await post('/auth/login', {
      id: toBase64Url(assertion.rawId),
      clientDataJSON: toBase64Url(assertion.response.clientDataJSON),
      authenticatorData: toBase64Url(assertion.response.authenticatorData),
      signature: toBase64Url(assertion.response.signature),
      userHandle: assertion.response.userHandle ? toBase64Url(assertion.response.userHandle) : ''
    });

    window.location = result.redirect || '/';
  }

  /* ------------------------------------------------ add to an account */

  async function addPasskey(button) {
    var csrf = button.dataset.csrf;
    var label = (document.getElementById('passkey-label') || {}).value || '';
    var options = await post('/account/passkeys/challenge', {}, csrf);
    var credential = await createCredential(options);
    credential.label = label;
    var result = await post('/account/passkeys', credential, csrf);
    window.location = result.redirect || '/account';
  }

  /* --------------------------------------------------------------- bind */

  function bind(button, action) {
    var status = document.getElementById(button.dataset.status || 'passkey-status');

    if (!supported()) {
      button.disabled = true;
      show(status, 'This browser does not support passkeys.');
      return;
    }

    button.addEventListener('click', async function (event) {
      event.preventDefault();
      button.disabled = true;
      show(status, '');

      try {
        await action(button);
      } catch (error) {
        var message = describe(error);
        if (message !== null) {
          show(status, message);
        }
      } finally {
        button.disabled = false;
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    var bindings = {
      'passkey-register': register,
      'passkey-signin': signIn,
      'passkey-add': addPasskey
    };
    Object.keys(bindings).forEach(function (id) {
      var button = document.getElementById(id);
      if (button) {
        bind(button, bindings[id]);
      }
    });
  });
})();
