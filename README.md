# google-drive-extractor

[![Docker Repository on Quay](https://quay.io/repository/keboola/google-drive-extractor/status "Docker Repository on Quay")](https://quay.io/repository/keboola/google-drive-extractor)
[![Build Status](https://travis-ci.org/keboola/google-drive-extractor.svg?branch=master)](https://travis-ci.org/keboola/google-drive-extractor)
[![Code Climate](https://codeclimate.com/github/keboola/google-drive-extractor/badges/gpa.svg)](https://codeclimate.com/github/keboola/google-drive-extractor)
[![Test Coverage](https://codeclimate.com/github/keboola/google-drive-extractor/badges/coverage.svg)](https://codeclimate.com/github/keboola/google-drive-extractor/coverage)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/keboola/google-drive-extractor/blob/master/LICENSE.md)

Extract data from Goole Drive files and spreadsheets.

## Example configuration

```yaml
parameters:
  outputBucket: "in.c-google-drive-extractor-testcfg1"
  sheets:
    -
      id: 0
      fileId: FILE_ID
      fileTitle: FILE_TITLE
      sheetId: THE_GID_OF_THE_SHEET
      sheetTitle: SHEET_TITLE
      outputTable: FILE_TITLE
      enabled: true
```

Note that this extractor is using [Keboola OAuth Bundle](https://github.com/keboola/oauth-v2-bundle) to store OAuth credentials.

## Development

App is developed on localhost using TDD.

1. Clone from repository: `git clone git@github.com:keboola/google-drive-extractor.git`
2. Change directory: `cd google-drive-extractor`
3. Install dependencies: `composer install --no-interaction`
4. Create `tests.sh` file from template `tests.sh.template`. 
5. You will need working OAuth credentials. 
    - Go to Googles [OAuth 2.0 Playground](https://developers.google.com/oauthplayground). 
    - In the configuration (the cog wheel on the top right side) check `Use your own OAuth credentials` and paste your OAuth Client ID and Secret.
    - Go through the authorization flow and generate `Access` and `Refresh` tokens. Copy and paste them into the `tests.sh` file.    
6. Run the tests: `./tests.sh`
