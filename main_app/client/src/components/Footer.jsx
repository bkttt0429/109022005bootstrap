import React from 'react';
import { Container, Row, Col } from 'react-bootstrap';

export default function Footer() {
    return (
        <footer className="bg-light py-4 mt-5 border-top">
            <Container>
                <Row>
                    <Col md={12} className="text-center text-muted">
                        <p className="mb-0">&copy; 2025 ERP System. All rights reserved.</p>
                    </Col>
                </Row>
            </Container>
        </footer>
    );
}
