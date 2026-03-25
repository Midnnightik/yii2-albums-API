# Unix-friendly entry point (avoids needing +x on scripts/run-local-docker.sh)
.PHONY: run-local

run-local:
	bash scripts/run-local-docker.sh
