#!/bin/bash

# Clone the repository (replace URL with your repository URL)
git clone --depth=1 https://github.com/undoLogic/setupCase-core.git tmpSetupCase

# we do not want any git association at all
rm -rf tmpSetupCase/.git