#!/usr/bin/env bash

# CodeBlocks Templates - include all template for codeBlocks PUBLIC
rsync -av ../sourceFiles/templates/CodeBlocks/. cakePHP/4.x/templates/CodeBlocks/.

# Templates
rsync -av ../sourceFiles/templates/Staff/. cakePHP/4.x/templates/Staff/.
rsync -av ../sourceFiles/templates/Manager/. cakePHP/4.x/templates/Manager/.

# Controllers
rsync -av ../sourceFiles/src/Controller/Staff/. cakePHP/4.x/src/Controller/Staff/.
rsync -av ../sourceFiles/src/Controller/Manager/. cakePHP/4.x/src/Controller/Manager/.
rsync -av ../sourceFiles/src/Controller/UsersController.php cakePHP/4.x/src/Controller/UsersController.php
rsync -av ../sourceFiles/src/Controller/CodeBlocksController.php cakePHP/4.x/src/Controller/CodeBlocksController.php
rsync -av ../sourceFiles/src/Controller/SetupPagesController.php cakePHP/4.x/src/Controller/SetupPagesController.php

# Models
rsync -av ../sourceFiles/src/Model/Table/CodeBlocksTable.php cakePHP/4.x/src/Model/Table/CodeBlocksTable.php
rsync -av ../sourceFiles/src/Model/Table/CodeBlockTypesTable.php cakePHP/4.x/src/Model/Table/CodeBlockTypesTable.php
rsync -av ../sourceFiles/src/Model/Table/FormAttemptsTable.php cakePHP/4.x/src/Model/Table/FormAttemptsTable.php
rsync -av ../sourceFiles/src/Model/Table/TranslationsTable.php cakePHP/4.x/src/Model/Table/TranslationsTable.php
rsync -av ../sourceFiles/src/Model/Table/Users.php cakePHP/4.x/src/Model/Table/Users.php

