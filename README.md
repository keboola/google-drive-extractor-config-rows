# google-drive-extractor

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/keboola/google-drive-extractor/blob/master/LICENSE.md)

Extract data from Goole Drive files and spreadsheets.

## Example configuration

```json
{
   "parameters": {
      "fileId": "FILE_ID",
      "fileTitle": "FILE_TITLE",
      "sheetId": "THE_GID_OF_THE_SHEET",
      "sheetTitle": "SHEET_TITLE",
      "outputTable": "FILE_TITLE"
   }
}
```

## OAuth Registration

Note that this extractor is using [Keboola OAuth Bundle](https://github.com/keboola/oauth-v2-bundle) to store OAuth credentials.

1. Create application in Google Developer console.

- Enable APIs: `Google Drive API`, ` Google Sheets API`
- Go to `Credentials` section and create new credentials of type `OAuth Client ID`. Use `https://SYRUP_INSTANCE.keboola.com/oauth-v2/authorize/keboola.ex-google-drive/callback` as redirec URI.

2. Register application in Keboola Oauth [http://docs.oauthv2.apiary.io/#reference/manage/addlist-supported-api/add-new-component](http://docs.oauthv2.apiary.io/#reference/manage/addlist-supported-api/add-new-component)


```
{ 
    "component_id": "keboola.ex-google-drive",
    "friendly_name": "Google Drive Extractor",
    "app_key": "XXX.apps.googleusercontent.com",
    "app_secret": "",
    "auth_url": "https://accounts.google.com/o/oauth2/v2/auth?response_type=code&redirect_uri=%%redirect_uri%%&client_id=%%client_id%%&access_type=offline&prompt=consent&scope=https://www.googleapis.com/auth/drive https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/spreadsheets.readonly",
    "token_url": "https://www.googleapis.com/oauth2/v4/token",
    "oauth_version": "2.0"
}
```

## Development

App is developed on localhost using TDD.

1. Clone from repository: `git clone git@github.com:keboola/google-drive-extractor.git`
2. Change directory: `cd google-drive-extractor`
3. Build docker image: `docker-compose build`
4. Install dependencies: `docker-compose run --rm dev composer install --no-scripts`
5. You will need working OAuth credentials. 
    - Go to Googles [OAuth 2.0 Playground](https://developers.google.com/oauthplayground). 
    - In the configuration (the cog wheel on the top right side) check `Use your own OAuth credentials` and paste your OAuth Client ID and Secret.
    - Go through the authorization flow and generate `Access` and `Refresh` tokens. Copy and paste them into the `tests.sh` file.    
5. Run the tests: `docker-compose run --rm dev composer tests`
