package com.queueflow.api.entity;

public enum BusinessCategory {

    HEALTH("Hospital / Clinic"),
    FINANCE("Bank / Service"),
    PUBLIC_SERVICE("Public Service"),
    RESTAURANT("Restaurant"),
    EVENT("Event"),
    RETAIL_TECH("Retail / Tech"),
    OTHER("Other");

    private final String label;

    BusinessCategory(String label) {
        this.label = label;
    }

    public String getLabel() {
        return label;
    }
}
