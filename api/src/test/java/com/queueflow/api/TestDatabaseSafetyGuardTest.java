package com.queueflow.api;

import org.junit.jupiter.api.Test;

import javax.sql.DataSource;
import java.io.PrintWriter;
import java.lang.reflect.Proxy;
import java.sql.Connection;
import java.sql.SQLException;
import java.sql.SQLFeatureNotSupportedException;
import java.util.logging.Logger;

import static org.assertj.core.api.Assertions.assertThatCode;
import static org.assertj.core.api.Assertions.assertThatThrownBy;

class TestDatabaseSafetyGuardTest {

    @Test
    void shouldAcceptQueueflowTestDatabase() {
        TestDatabaseSafetyGuard guard = new TestDatabaseSafetyGuard();

        assertThatCode(() -> guard.postProcessAfterInitialization(
                dataSourceForCatalog("queueflow_test"),
                "dataSource"
        ))
                .doesNotThrowAnyException();
    }

    @Test
    void shouldRejectDevelopmentDatabase() {
        TestDatabaseSafetyGuard guard = new TestDatabaseSafetyGuard();

        assertThatThrownBy(() -> guard.postProcessAfterInitialization(
                dataSourceForCatalog("queueflow"),
                "dataSource"
        ))
                .isInstanceOf(IllegalStateException.class)
                .hasMessageContaining("queueflow_test");
    }

    @Test
    void shouldRejectBackupTestDatabaseName() {
        TestDatabaseSafetyGuard guard = new TestDatabaseSafetyGuard();

        assertThatThrownBy(() -> guard.postProcessAfterInitialization(
                dataSourceForCatalog("queueflow_test_backup"),
                "dataSource"
        ))
                .isInstanceOf(IllegalStateException.class)
                .hasMessageContaining("queueflow_test");
    }

    @Test
    void shouldRejectSimilarTestDatabaseName() {
        TestDatabaseSafetyGuard guard = new TestDatabaseSafetyGuard();

        assertThatThrownBy(() -> guard.postProcessAfterInitialization(
                dataSourceForCatalog("queueflow_test_evil"),
                "dataSource"
        ))
                .isInstanceOf(IllegalStateException.class)
                .hasMessageContaining("queueflow_test");
    }

    private static DataSource dataSourceForCatalog(String catalog) {
        return new DataSource() {
            @Override
            public Connection getConnection() {
                return connectionForCatalog(catalog);
            }

            @Override
            public Connection getConnection(
                    String username,
                    String password
            ) {
                return connectionForCatalog(catalog);
            }

            @Override
            public PrintWriter getLogWriter() throws SQLException {
                throw new SQLFeatureNotSupportedException();
            }

            @Override
            public void setLogWriter(
                    PrintWriter out
            ) throws SQLException {
                throw new SQLFeatureNotSupportedException();
            }

            @Override
            public void setLoginTimeout(
                    int seconds
            ) throws SQLException {
                throw new SQLFeatureNotSupportedException();
            }

            @Override
            public int getLoginTimeout() throws SQLException {
                throw new SQLFeatureNotSupportedException();
            }

            @Override
            public Logger getParentLogger() throws SQLFeatureNotSupportedException {
                throw new SQLFeatureNotSupportedException();
            }

            @Override
            public <T> T unwrap(
                    Class<T> iface
            ) throws SQLException {
                throw new SQLFeatureNotSupportedException();
            }

            @Override
            public boolean isWrapperFor(
                    Class<?> iface
            ) {
                return false;
            }
        };
    }

    private static Connection connectionForCatalog(String catalog) {
        return (Connection) Proxy.newProxyInstance(
                TestDatabaseSafetyGuardTest.class.getClassLoader(),
                new Class<?>[]{Connection.class},
                (proxy, method, args) -> switch (method.getName()) {
                    case "getCatalog" -> catalog;
                    case "close" -> null;
                    case "isClosed" -> false;
                    default -> throw new SQLFeatureNotSupportedException(
                            method.getName()
                    );
                }
        );
    }
}
