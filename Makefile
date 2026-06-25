SHELL := /bin/sh

PHP ?= php
COMPOSER ?= composer
ARTISAN := $(PHP) artisan
HOST ?= 127.0.0.1
PORT ?= 8000

.DEFAULT_GOAL := help

.PHONY: help platform install-tools install-tools-macos install-tools-linux install-tools-windows doctor install env env-sqlite key bootstrap setup setup-sqlite migrate seed fresh serve test lint queue schedule clear optimize routes

help:
	@printf "\nExam Portal local commands\n"
	@printf "  make platform    Show detected OS/platform\n"
	@printf "  make install-tools Install PHP, Composer, and Make where supported\n"
	@printf "  make bootstrap   install-tools + setup-sqlite + test\n"
	@printf "  make doctor      Check required local tools\n"
	@printf "  make install     Install Composer dependencies\n"
	@printf "  make env         Create .env from .env.example if missing\n"
	@printf "  make env-sqlite  Configure .env for local SQLite/array drivers\n"
	@printf "  make key         Generate Laravel APP_KEY\n"
	@printf "  make setup       env + install + key + migrate + seed\n"
	@printf "  make setup-sqlite env-sqlite + install + key + fresh\n"
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

platform:
	@uname -s

install-tools:
	@case "$$(uname -s)" in \
		Darwin) $(MAKE) install-tools-macos ;; \
		Linux) $(MAKE) install-tools-linux ;; \
		MINGW*|MSYS*|CYGWIN*) $(MAKE) install-tools-windows ;; \
		*) printf "Unsupported platform: $$(uname -s)\nInstall PHP 8.3+, Composer, and Make manually, then run make setup-sqlite.\n"; exit 1 ;; \
	esac

install-tools-macos:
	@command -v brew >/dev/null 2>&1 || { printf "Homebrew is required on macOS: https://brew.sh\n"; exit 1; }
	brew install php composer make

install-tools-linux:
	@if command -v apt-get >/dev/null 2>&1; then \
		sudo apt-get update; \
		sudo apt-get install -y make unzip curl sqlite3 composer php-cli php-mbstring php-xml php-curl php-zip php-sqlite3 php-pgsql; \
	elif command -v dnf >/dev/null 2>&1; then \
		sudo dnf install -y make unzip curl sqlite composer php-cli php-mbstring php-xml php-curl php-zip php-sqlite3 php-pgsql; \
	else \
		printf "Install PHP 8.3+, Composer, Make, SQLite, and PHP SQLite/PostgreSQL extensions with your Linux package manager.\n"; \
		exit 1; \
	fi

install-tools-windows:
	@printf "Native Windows does not include make by default.\n"
	@printf "Recommended: use WSL Ubuntu, then run: make install-tools-linux\n"
	@printf "Alternative PowerShell setup:\n"
	@printf "  winget install PHP.PHP Composer.Composer GnuWin32.Make\n"
	@printf "Then reopen the terminal and run: make setup-sqlite\n"

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

env-sqlite: env doctor
	@mkdir -p database
	@touch database/database.sqlite
	@$(PHP) -r '$$path=".env"; $$env=file_get_contents($$path); $$replacements=["DB_CONNECTION"=> "sqlite", "DB_DATABASE"=> "database/database.sqlite", "DB_USERNAME"=> "", "DB_PASSWORD"=> "", "CACHE_STORE"=> "array", "QUEUE_CONNECTION"=> "sync", "SESSION_DRIVER"=> "array"]; foreach ($$replacements as $$key => $$value) { if (preg_match("/^".$$key."=.*/m", $$env)) { $$env=preg_replace("/^".$$key."=.*/m", $$key."=".$$value, $$env); } else { $$env .= PHP_EOL.$$key."=".$$value; } } file_put_contents($$path, $$env);'
	@printf ".env configured for local SQLite.\n"

key: env
	$(ARTISAN) key:generate

bootstrap: install-tools setup-sqlite test

setup: env install key migrate seed

setup-sqlite: env-sqlite install key fresh

migrate:
	$(ARTISAN) migrate

seed:
	$(ARTISAN) db:seed --class=DemoSeeder

fresh:
	$(ARTISAN) migrate:fresh --seed

serve:
	$(ARTISAN) serve --host=$(HOST) --port=$(PORT)

test:
	vendor/bin/phpunit

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
