FROM restic/restic:0.18.0 AS restic
FROM mysql:8.4
COPY --from=restic /usr/bin/restic /usr/local/bin/restic
COPY docker/backup/backup.sh /usr/local/bin/totemsync-backup
USER mysql
ENV RESTIC_CACHE_DIR=/tmp/restic-cache
ENTRYPOINT ["/bin/bash"]
CMD ["-c", "while :; do bash /usr/local/bin/totemsync-backup; sleep 86400; done"]
