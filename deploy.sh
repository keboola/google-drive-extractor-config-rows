#!/bin/bash
docker login -u="$QUAY_USERNAME" -p="$QUAY_PASSWORD" quay.io
docker tag googledriveextractor_app quay.io/keboola/google-drive-extractor:$TRAVIS_TAG
docker tag googledriveextractor_app quay.io/keboola/google-drive-extractor:latest
docker images
docker push quay.io/keboola/google-drive-extractor:$TRAVIS_TAG
docker push quay.io/keboola/google-drive-extractor:latest
