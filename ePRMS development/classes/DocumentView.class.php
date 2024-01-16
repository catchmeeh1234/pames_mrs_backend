<?php
    class DocumentView extends Document {
        public function displayDocuments() {
            return $this->fetchDocuments();
        }
    }