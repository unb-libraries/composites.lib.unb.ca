FROM unblibraries/dockworker-drupal:latest
MAINTAINER UNB Libraries <libsupport@unb.ca>

ENV DRUPAL_SITE_ID comp
ENV DRUPAL_SITE_URI composites.lib.unb.ca
ENV DRUPAL_SITE_UUID 022dab87-328e-494c-b8f8-ebde1e1a0162

# Override upstream scripts with local.
COPY ./scripts/container /scripts

# Add additional OS packages.
ENV ADDITIONAL_OS_PACKAGES tiff-dev tiff postfix imagemagick rsyslog postfix php7-ldap php7-xmlreader php7-zip php7-redis
RUN /scripts/addOsPackages.sh && \
  /scripts/initRsyslog.sh && \
  echo "TLS_REQCERT never" > /etc/openldap/ldap.conf

# Add package conf.
COPY ./package-conf /package-conf
RUN /scripts/setupStandardConf.sh

# Build the contrib Drupal tree.
ARG COMPOSER_DEPLOY_DEV=no-dev
ENV DRUPAL_BASE_PROFILE minimal
ENV DRUPAL_BUILD_TMPROOT ${TMP_DRUPAL_BUILD_DIR}/webroot

COPY ./build/ ${TMP_DRUPAL_BUILD_DIR}
RUN /scripts/build.sh ${COMPOSER_DEPLOY_DEV} ${DRUPAL_BASE_PROFILE}

# Deploy repo assets.
COPY ./tests/ ${DRUPAL_TESTING_ROOT}/
COPY ./config-yml ${TMP_DRUPAL_BUILD_DIR}/config-yml
COPY ./custom/themes ${TMP_DRUPAL_BUILD_DIR}/custom_themes
COPY ./custom/modules ${TMP_DRUPAL_BUILD_DIR}/custom_modules

# Universal environment variables.
ENV DEPLOY_ENV prod
ENV DRUPAL_DEPLOY_CONFIGURATION TRUE
ENV DRUPAL_CONFIGURATION_EXPORT_SKIP devel

# Metadata
ARG BUILD_DATE
ARG VCS_REF
ARG VERSION
LABEL ca.unb.lib.generator="drupal8" \
      com.microscaling.docker.dockerfile="/Dockerfile" \
      com.microscaling.license="MIT" \
      org.label-schema.build-date=$BUILD_DATE \
      org.label-schema.description="composites.lib.unb.ca is the composites application for staff at UNB Libraries." \
      org.label-schema.name="composites.lib.unb.ca" \
      org.label-schema.schema-version="1.0" \
      org.label-schema.url="https://composites.lib.unb.ca" \
      org.label-schema.vcs-ref=$VCS_REF \
      org.label-schema.vcs-url="https://github.com/unb-libraries/composites.lib.unb.ca" \
      org.label-schema.vendor="University of New Brunswick Libraries" \
      org.label-schema.version=$VERSION
