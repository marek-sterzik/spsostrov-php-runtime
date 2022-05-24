FROM node:16

ARG API_WWW_DATA_UID=orig
ARG API_WWW_DATA_GID=orig

# fix www-data uid and gid
ADD fix-node-user /usr/local/sbin/fix-node-user
RUN fix-node-user "${API_WWW_DATA_UID}" "${API_WWW_DATA_GID}"
RUN chown -R node:node /home/node

WORKDIR /workspace
