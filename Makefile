SHELL := /bin/sh

PHP ?= php
COMPOSER ?= composer
ARTISAN := $(PHP) artisan
HOST ?= 127.0.0.1
PORT ?= 8000

.DEFAULT_GOAL := help

.PHONY: help doctor install env key setup migrate seed fresh serve test lint queue schedule clear optimize routes

help:
	@printf "\nExam Portal local commands\n"
	@printf "  make doctor      Check required local tools\n"
	@printf "  make install     Install Composer dependencies\n"
	@printf "  make env         Create .env from .env.example if missing\n"
	@printf "  make key         Generate Laravel APP_KEY\n"
	@printf "  make setup       env + install + key + migrate + seed\n"
	@printf "  make migrate     Run database migrations\n"
	@printf "  make seed        Seed local demo data\n"
	@printf "  make fresh       Rebuild local database and seed demo data\n"
	@printf "  make serve       Start local API server on HOST/PORT\n"
	@printf "  make test        Run Laravel tests\n"
	@printf "  make lint        Run Laravel Pint in check mode\n"
	@printf "  make queue       Start queue worker\n"
	@printf "  make schedule    Run Laravel scheduler once\n"
	@printf "  make clear       Clear Laravel caches\n"
	@printf "  make routes      List registered routes\n\n"

doctor:
	@command -v $(PHP) >/dev/null 2>&1 || { printf "Missing php. Install PHP 8.3+ first.\n"; exit 1; }
	@command -v $(COMPOSER) >/dev/null 2>&1 || { printf "Missing composer. Install Composer first.\n"; exit 1; }
	@$(PHP) -v
	@$(COMPOSER) --version

install: doctor
	$(COMPOSER) install

env:
	@test -f .env || cp .env.example .env
	@printf ".env is ready.\n"

key: env
	$(ARTISAN) key:generate

setup: env install key migrate seed

migrate:
	$(ARTISAN) migrate

seed:
	$(ARTISAN) db:seed --class=DemoSeeder

fresh:
	$(ARTISAN) migrate:fresh --seed

serve:
	$(ARTISAN) serve --host=$(HOST) --port=$(PORT)

test:
	$(ARTISAN) test

lint:
	vendor/bin/pint --test

queue:
	$(ARTISAN) queue:work

schedule:
	$(ARTISAN) schedule:run

clear:
	$(ARTISAN) optimize:clear

optimize:
	$(ARTISAN) optimize

routes:
	$(ARTISAN) route:list
