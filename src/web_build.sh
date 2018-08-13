#!/bin/bash
gulp build
cp -rf ./web/Public/src/static ./web/Public/dist
cp -rf ./web/Public/src/Common ./web/Public/dist
cp -rf ./web/Public/src/Games  ./web/Public/dist
cp -rf ./web/Public/src/Home  ./web/Public/dist
cp -rf ./web/Public/src/sl  ./web/Public/dist