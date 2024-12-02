<?php

namespace Kiniauth\ValueObjects\ImportExport;

enum ProjectImportResourceStatus {
    case Create;
    case Update;
    case Ignore;
    case Delete;
}