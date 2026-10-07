package com.queueflow.api;

import org.springframework.beans.BeansException;
import org.springframework.beans.factory.config.BeanPostProcessor;
import org.springframework.stereotype.Component;

import javax.sql.DataSource;
import java.sql.Connection;
import java.sql.SQLException;

@Component
class TestDatabaseSafetyGuard implements BeanPostProcessor {

    private static final String REQUIRED_DATABASE_NAME =
            "queueflow_test";

    @Override
    public Object postProcessAfterInitialization(
            Object bean,
            String beanName
    ) throws BeansException {
        if (! (bean instanceof DataSource dataSource)) {
            return bean;
        }

        String databaseName = databaseName(dataSource);

        if (! REQUIRED_DATABASE_NAME.equals(databaseName)) {
            throw new IllegalStateException(
                    "Refusing to run Spring integration tests against non-test database. "
                            + "Integration tests require the `"
                            + REQUIRED_DATABASE_NAME
                            + "` database."
            );
        }

        return bean;
    }

    private String databaseName(
            DataSource dataSource
    ) {
        try (Connection connection = dataSource.getConnection()) {
            return connection.getCatalog();
        } catch (SQLException exception) {
            throw new IllegalStateException(
                    "Unable to verify Spring test database safety.",
                    exception
            );
        }
    }
}


