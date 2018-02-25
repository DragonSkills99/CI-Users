Just put the files under Project in the destinated directories. After that you only have to call:

    DOMAIN + BASEPATH + /users/install
    
e.g. your domain is `https://urdomain.com` and your base path `/CodeIgniter` the installation path would be `https://urdomain.com/CodeIgniter/users/install`.

Then the database will be set up.

After that you will have to set up an gmail connection to send the E-Mails for verifications or password resets. First create an `API Project` in the [Google Developer Console](https://console.developers.google.com) afterwards activate the [Gmail API](https://console.developers.google.com/apis/library/gmail.googleapis.com).
Now create an [OAuth-Client-ID](https://console.developers.google.com/apis/credentials) (The redirect uri should be `DOMAIN + BASEPATH + /mail/auth`), download the secrets JSON, rename it to `secret.json` and replace it with the `secret.json` in the `application` directory. At last you just have to call `DOMAIN + BASEPATH + /mail/auth`, log in into gmail, authorize your website and the email connecting will be set up.
