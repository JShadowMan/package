#!/usr/bin/env python
#
# Copyright (C) 2017 DL
#
import logging
from error_logger.utils import exceptions


__all__ = ['init', 'info', 'warning', 'error', 'debug', 'logger']

_wait_message_queue = []  # type: list

logger = None

g_level = None


def _logger_level(level):
    level = str(level).upper()
    if level == 'DEBUG':
        return logging.DEBUG
    elif level == 'INFO':
        return logging.INFO
    elif level == 'WARNING':
        return logging.WARNING
    elif level == 'ERROR':
        return logging.ERROR
    else:
        raise TypeError("Invalid logging level '{level}'".format(level=level))


def wait_logger_init_msg(func, message):
    _wait_message_queue.append((func, message))


def init(level, console=False, log_file=None):
    global logger, g_level
    if isinstance(level, str):
        level = _logger_level(level)
    if console is True:
        level = logging.DEBUG
    g_level = level
    logging.basicConfig(level=level, format='')

    logger = logging.getLogger()
    logger.setLevel(level)
    while len(logger.handlers):
        logger.removeHandler(logger.handlers[0])

    formatter = logging.Formatter('%(asctime)-12s: %(levelname)-8s %(message)s',
                                  '%m/%d/%Y %H:%M:%S')
    if not (log_file is False) and console is False:
        if log_file is None:
            # Only run under *nix
            log_file = '/tmp/websocket_server.log'
        handler = logging.FileHandler(log_file)
        handler.setLevel(level)

        handler.setFormatter(formatter)
        logging.getLogger('').addHandler(handler)

    if console is True:
        console = logging.StreamHandler()
        console.setLevel(logging.DEBUG)
        console.setFormatter(formatter)
        logging.getLogger('').addHandler(console)
        wait_logger_init_msg(warning, 'Logger file handler is disable')

    if log_file is False and console is False:
        raise exceptions.LoggerWarning('Logger is turn off!')

    for func, message in _wait_message_queue:
        if callable(func):
            func(message)


def debug(message):
    logging.debug(message)


def info(message):
    logging.info(message)


def warning(message):
    logging.warning(message)


def error(message):
    logging.error(message)


def error_exit(message):
    error(message)
    exit()
