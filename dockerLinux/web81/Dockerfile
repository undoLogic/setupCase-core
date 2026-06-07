FROM undologic/setupcase-base

ARG UID=1000
ARG GID=1000

# --------------------------------------------------
# User / permissions (shared)
# --------------------------------------------------
RUN groupmod -g ${GID} www-data \
 && usermod -u ${UID} www-data

# --------------------------------------------------
# Apache (CakePHP primary)
# --------------------------------------------------
RUN a2enmod rewrite ssl

# --------------------------------------------------
# System packages (shared across PHP, mobile, localApp)
# --------------------------------------------------
RUN apt-get update && apt-get install -y \
        dos2unix \
        zip \
        unzip \
        rsync \
        mariadb-client \
        subversion \
        curl \
        libcurl4-gnutls-dev \
        libkrb5-dev \
        libc-client-dev \
        screen \
        vim \
        git \
        imagemagick \
        libmagickwand-dev \
        tree \
        \
        # ---- Python support (OPTIONAL: used by localApp template) ----
        python3 \
        python3-venv \
        python3-pip \
        \
        --no-install-recommends \
    && rm -rf /var/lib/apt/lists/*

# --------------------------------------------------
# PHP extensions (CakePHP)
# --------------------------------------------------
RUN docker-php-ext-install pdo pdo_mysql curl sockets mysqli

RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
 && docker-php-ext-install imap

RUN apt-get update \
 && apt-get install -y libicu-dev \
 && docker-php-ext-configure intl \
 && docker-php-ext-install intl \
 && rm -rf /var/lib/apt/lists/*

# --------------------------------------------------
# Composer (PHP dependency management)
# --------------------------------------------------
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN curl -sS https://getcomposer.org/installer | php \
 && mv composer.phar /usr/local/bin/composer \
 && composer --version

# --------------------------------------------------
# Prepare Poetry directories for www-data
# Poetry REQUIRES this exact path to exist & be writable
# --------------------------------------------------
ENV HOME=/var/www

RUN mkdir -p /var/www/.local/share/pypoetry \
 && chown -R www-data:www-data /var/www/.local

# --------------------------------------------------
# Poetry (Python dependency management)
# Installed into /var/www/.local/bin
# --------------------------------------------------
ENV PATH="/var/www/.local/bin:$PATH"

RUN curl -sSL https://install.python-poetry.org | python3 -

# Configure Poetry to keep virtualenvs inside project folders
RUN poetry config virtualenvs.in-project true

# Set a safe temp dir and ensure PATH includes npm globals
ENV TMPDIR=/tmp
ENV PATH="/usr/local/bin:$PATH"

# --------------------------------------------------
# Node.js (Required for Codex CLI)
# --------------------------------------------------
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && node -v \
    && npm -v

# --------------------------------------------------
# Codex CLI
# --------------------------------------------------
RUN npm install -g @openai/codex

RUN codex --version

# Ensure www-data has a real home with writable shell config
RUN mkdir -p /var/www \
    && touch /var/www/.bashrc \
    && touch /var/www/.profile \
    && chown -R www-data:www-data /var/www

# --------------------------------------------------
# Runtime user / paths
# --------------------------------------------------
WORKDIR /var/www/vhosts/website.com/www

USER www-data

EXPOSE 80
EXPOSE 443
