FROM php:8.2-apache

# PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Python 3 + venv
RUN apt-get update && apt-get install -y python3 python3-pip python3-venv && \
    python3 -m venv /opt/venv && \
    /opt/venv/bin/pip install --no-cache-dir \
        pandas numpy scikit-learn scipy \
        sqlalchemy mysql-connector-python

ENV PATH="/opt/venv/bin:$PATH"

# Copy project files
COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html
