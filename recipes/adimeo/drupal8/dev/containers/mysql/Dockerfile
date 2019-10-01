# Derived from official mysql image (our base image)
FROM mysql:5.7

# All scripts in docker-entrypoint-initdb.d/ are automatically
# executed during container startup
COPY ./sql-scripts/ /docker-entrypoint-initdb.d/
WORKDIR /docker-entrypoint-initdb.d
RUN gunzip *.gz
