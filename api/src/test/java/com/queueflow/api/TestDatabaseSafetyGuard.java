package com.queueflow.api;

import org.springframework.beans.factory.SmartInitializingSingleton;
import org.springframework.stereotype.Component;

import javax.sql.DataSource;
import java.sql.Connection;
import java.sql.SQLException;

@Component
class TestDatabaseSafetyGuard implements SmartInitializingSingleton {

    private static final String REQUIRED_DATABASE_NAME =
            "queueflow_test";

    private static final String REQUIRED_JDBC_URL_PREFIX =
            "jdbc:postgresql://localhost:5433/"
                    + REQUIRED_DATABASE_NAME;

    private final DataSource dataSource;

    TestDatabaseSafetyGuard(
            DataSource dataSource
    ) {
        this.dataSource = dataSource;
    }

    @Override
    public void afterSingletonsInstantiated() {
        String jdbcUrl = jdbcUrl();

        if (! jdbcUrl.equals(REQUIRED_JDBC_URL_PREFIX)
                && ! jdbcUrl.startsWith(REQUIRED_JDBC_URL_PREFIX + "?")) {
            throw new IllegalStateException(
                    "Refusing to run Spring integration tests against non-test database. "
                            + "Expected JDBC URL to start with: "
                            + REQUIRED_JDBC_URL_PREFIX
            );
        }
    }

    private String jdbcUrl() {
        try (Connection connection = dataSource.getConnection()) {
            return connection.getMetaData().getURL();
        } catch (SQLException exception) {
            throw new IllegalStateException(
                    "Unable to verify Spring test database safety.",
                    exception
            );
        }
    }
}
