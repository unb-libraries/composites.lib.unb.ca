FROM ghcr.io/unb-libraries/drupal:10.x-1.x-unblib

ENV ADDITIONAL_OS_PACKAGES="tiff-dev tiff postfix imagemagick bash postfix php-ldap php-xmlreader php-zip php81-pecl-redis"
ENV DRUPAL_SITE_ID="comp"
ENV DRUPAL_SITE_URI="composites.lib.unb.ca"
ENV DRUPAL_SITE_UUID="022dab87-328e-494c-b8f8-ebde1e1a0162"

# Build application.
COPY ./build/ /build/
RUN ${RSYNC_MOVE} /build/scripts/container/ /scripts/ && \
  /scripts/addOsPackages.sh && \
  /scripts/initOpenLdap.sh && \
  /scripts/setupStandardConf.sh && \
  curl -O https://raw.githubusercontent.com/VoidVolker/MagickSlicer/master/magick-slicer.sh && \
  mv magick-slicer.sh /usr/local/bin/magick-slicer && \
  chmod +x /usr/local/bin/magick-slicer && \
  /scripts/build.sh

# Deploy configuration.
COPY ./configuration ${DRUPAL_CONFIGURATION_DIR}
RUN /scripts/pre-init.d/72_secure_config_sync_dir.sh

# Deploy custom modules, themes.
COPY ./custom/themes ${DRUPAL_ROOT}/themes/custom
COPY ./custom/modules ${DRUPAL_ROOT}/modules/custom

# Container metadata.
LABEL ca.unb.lib.generator="drupal9" \
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
  org.label-schema.version=$VERSION \
  org.opencontainers.image.authors="UNB Libraries <libsupport@unb.ca>" \
  org.opencontainers.image.source="https://github.com/unb-libraries/composites.lib.unb.ca"
