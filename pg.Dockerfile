# Base image
FROM postgres:13

# Copy PostgreSQL configuration files
COPY /postgres/postgresql.conf /etc/postgresql/postgresql.conf
COPY /postgres/pg_hba.conf /etc/postgresql/pg_hba.conf

# Start the PostgreSQL service
CMD ["postgres", "-c", "config_file=/etc/postgresql/postgresql.conf"]
