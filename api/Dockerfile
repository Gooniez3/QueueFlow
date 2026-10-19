# Stage 1: Build the Spring Boot application
FROM maven:3.9-eclipse-temurin-21 AS build

WORKDIR /build

COPY pom.xml .
COPY src ./src

RUN mvn -B -DskipTests package

# Stage 2: Run the application
FROM eclipse-temurin:21-jre

WORKDIR /app

RUN groupadd --system queueflow \
    && useradd --system --gid queueflow queueflow

COPY --from=build --chown=queueflow:queueflow \
    /build/target/*.jar app.jar

USER queueflow

EXPOSE 8080

ENTRYPOINT ["java", "-jar", "/app/app.jar"]
